<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\GeminiAIService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatbotController extends Controller
{
    private GeminiAIService $gemini;

    public function __construct(GeminiAIService $gemini)
    {
        $this->gemini = $gemini;
    }

    public function context(Request $request)
    {
        $user = Auth::user() ?? Auth::guard('web')->user() ?? $request->user();
        $role = $user ? $user->role : 'guest';
        return response()->json([
            'role'            => $role,
            'name'            => $user?->name,
            'welcome_message' => $this->getWelcomeMessage($role, $user),
            'chips'           => $this->getDefaultChips($role),
            'mode_label'      => $this->getModeLabel($role),
            'mode_color'      => $this->getModeColor($role),
            'ai_powered'      => $this->gemini->isConfigured(),
        ]);
    }

    public function message(Request $request)
    {
        $request->validate([
            'message'        => 'required|string|max:500',
            'session_id'     => 'nullable|string',
            'history'        => 'nullable|array',
            'history.*.role' => 'nullable|string|in:user,bot',
            'history.*.text' => 'nullable|string|max:1000',
        ]);
        $sessionId = $request->input('session_id')
            ?: ($request->hasSession() ? 'session_'.md5((string) $request->session()->getId()) : 'session_'.uniqid());
        $rawMessage = trim($request->message);
        $message    = strtolower($rawMessage);
        $history    = $request->input('history', []);
        $user       = Auth::user() ?? Auth::guard('web')->user() ?? $request->user();
        $role       = $user ? $user->role : 'guest';
        if ($user && in_array($user->role, ['admin', 'staff']) && $request->filled('simulated_role')) {
            $simRole = $request->input('simulated_role');
            if (in_array($simRole, ['guest', 'tourist', 'staff', 'admin'])) {
                $role = $simRole;
            }
        }
        $result = match ($role) {
            'tourist' => $this->handleTourist($message, $rawMessage, $user ?? new User(['name' => 'Sample Tourist', 'role' => 'tourist']), $history),
            'staff'   => $this->handleStaff($message, $rawMessage, $user ?? new User(['name' => 'Sample Staff', 'role' => 'staff']), $history),
            'admin'   => $this->handleAdmin($message, $rawMessage, $user ?? new User(['name' => 'Admin User', 'role' => 'admin']), $history),
            default   => $this->handleGuest($message, $rawMessage, $history),
        };
        try {
            ChatbotLog::create([
                'user_id'    => $user?->id,
                'session_id' => $sessionId,
                'message'    => $rawMessage,
                'response'   => $result['response'],
                'intent'     => $result['intent'] ?? 'ai_response',
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to persist chatbot log', ['error' => $e->getMessage()]);
        }
        return response()->json([
            'response'   => $result['response'],
            'reply'      => $result['response'],
            'intent'     => $result['intent'] ?? 'ai_response',
            'chips'      => $result['chips'] ?? $this->getDefaultChips($role),
            'role'       => $role,
            'session_id' => $sessionId,
            'escalate'   => $result['escalate'] ?? false,
            'ai_powered' => $result['ai_powered'] ?? false,
        ]);
    }
    // GUEST ENGINE
    private function handleGuest(string $message, string $raw, array $history): array
    {
        if ($this->matchesAny($message, ['my booking', 'my reservation', 'my payment', 'my account', 'my receipt', 'check my', 'cancel my'])) {
            return ['response' => "To access your personal bookings and account details, please **log in** to your account.\n\nNew here? **Register** to get started, it is free!", 'intent' => 'auth_required', 'chips' => ['Login', 'Register', 'How to Book', 'Room Rates'], 'ai_powered' => false];
        }
        if (preg_match('/(TBRS?-[A-Z0-9]{4,10})/i', $raw)) {
            return ['response' => "To check a booking reference, please **log in** to your account first.\n\nClick **Login / Register** on the menu above.", 'intent' => 'auth_required', 'chips' => ['Login', 'Register', 'Room Rates', 'Directions'], 'ai_powered' => false];
        }
        $minRoom    = AccommodationUnit::rooms()->min('price_per_night') ?? 1000;
        $maxRoom    = AccommodationUnit::rooms()->max('price_per_night') ?? 1500;
        $minCottage = AccommodationUnit::cottages()->min('price_per_night') ?? 1200;
        $maxCottage = AccommodationUnit::cottages()->max('price_per_night') ?? 2000;
        $avail      = AccommodationUnit::available()->count();
        $today      = now()->format('Y-m-d');
        $cap        = CapacitySchedule::getCapacityForDate($today);
        $maxCap     = $cap ? $cap->max_capacity : (int) SystemSetting::get('daily_visitor_cap', 100);
        $curr       = $cap ? $cap->current_count : 0;
        $remaining  = max(0, $maxCap - $curr);
        $minRoomFmt    = number_format($minRoom);
        $maxRoomFmt    = number_format($maxRoom);
        $minCottageFmt = number_format($minCottage);
        $maxCottageFmt = number_format($maxCottage);
        $persona      = SystemSetting::get('chatbot_persona', 'You are the friendly AI tourism assistant for Talisay Beach Resort, located in Baybay City, Leyte, Philippines.');
        $customRules  = SystemSetting::get('chatbot_custom_rules', '');
        $announcement = SystemSetting::get('chatbot_announcement', '');

        $extraContext = '';
        if (!empty($customRules)) {
            $extraContext .= "\nRESORT POLICIES & CUSTOM RULES:\n" . $customRules . "\n";
        }
        if (!empty($announcement)) {
            $extraContext .= "\nCURRENT RESORT ANNOUNCEMENT:\n" . $announcement . "\n";
        }

        $systemPrompt  = "{$persona}\n\n"
            . "YOUR ROLE: Talking to a PUBLIC VISITOR (not logged in). You are a tourism and resort information assistant ONLY.\n\n"
            . "LIVE RESORT DATA (use these exact numbers):\n"
            . "- Rooms: PHP{$minRoomFmt} to PHP{$maxRoomFmt} per night (up to 4 guests, AC, Wi-Fi, private bath)\n"
            . "- Cottages: PHP{$minCottageFmt} to PHP{$maxCottageFmt} per night (up to 10 guests, oceanfront)\n"
            . "- Full-Resort Booking: Exclusive events (weddings, reunions, corporate)\n"
            . "- Available Units: {$avail} | Visitor Count Today: {$curr} of {$maxCap} ({$remaining} slots left)\n"
            . "- Check-In: 2:00 PM | Check-Out: 12:00 PM | Day Tour: 7:00 AM to 5:00 PM\n"
            . "- Restaurant: 6:00 AM to 10:00 PM\n"
            . "- Location: Barangay Maslug, Baybay City, Leyte, Philippines\n"
            . "- From Tacloban: about 2 hours | From Ormoc: about 1 hour\n"
            . "- Contact: +63 (053) 563-7000\n"
            . "- Payment: GCash, Cash on Arrival, Credit/Debit Card, PayPal\n"
            . "- Amenities: swimming pool, snorkeling reef, water sports, oceanview restaurant, pavilion, event hall\n"
            . "- 360 degree Virtual Tour available on the website\n"
            . $extraContext . "\n"
            . "STRICT RULES:\n"
            . "1. NEVER provide private/internal info (admin data, booking management, staff operations, user lists, financials)\n"
            . "2. If asked about personal bookings or things needing login, tell them to log in or register\n"
            . "3. If asked about admin/staff functions, say that is for staff only\n"
            . "4. Keep responses concise and friendly (max 5-6 lines)\n"
            . "5. Encourage visitors to Register or Login when relevant\n"
            . "6. Always respond in English\n"
            . "7. If asked about something unrelated to the resort, politely redirect to resort topics";
        if ($this->gemini->isConfigured()) {
            try {
                $aiResponse = $this->gemini->chat($systemPrompt, $raw, $history);
                return ['response' => $aiResponse, 'intent' => 'ai_response', 'chips' => $this->getDefaultChips('guest'), 'ai_powered' => true];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Chatbot AI fallback: ' . preg_replace('/key=[^&\s]+/', 'key=redacted', $e->getMessage()));
            }
        }
        return $this->keywordFallbackGuest($message, $raw, $minRoom, $maxRoom, $minCottage, $maxCottage, $avail, $curr, $maxCap, $remaining);
    }

    // TOURIST ENGINE
    private function handleTourist(string $message, string $raw, User $user, array $history): array
    {
        if ($this->matchesAny($message, ['all bookings', 'all users', 'all payments', 'all guests', 'manage user', 'user list', 'admin', 'total revenue', 'system setting'])) {
            return ['response' => "That information is only accessible to resort administrators. I can only help you with **your own** bookings, payments, and account-related information.", 'intent' => 'permission_denied', 'chips' => ['My Bookings', 'My Payments', 'Check Availability', 'Contact Support'], 'ai_powered' => false];
        }
        $myBookings = Booking::where('user_id', $user->id)->orderBy('created_at', 'desc')->take(5)->with(['accommodationUnit', 'payment'])->get();
        $myPayments = Payment::whereHas('booking', fn($q) => $q->where('user_id', $user->id))->orderBy('created_at', 'desc')->take(3)->get();
        $myReviews  = Review::where('user_id', $user->id)->count();
        $avail      = AccommodationUnit::available()->count();
        $minRoom    = AccommodationUnit::rooms()->min('price_per_night') ?? 1000;
        $maxRoom    = AccommodationUnit::rooms()->max('price_per_night') ?? 1500;
        $minCottage = AccommodationUnit::cottages()->min('price_per_night') ?? 1200;
        $maxCottage = AccommodationUnit::cottages()->max('price_per_night') ?? 2000;
        $bookingData = $myBookings->isEmpty() ? 'No bookings yet.'
            : $myBookings->map(function ($b) {
                $unit   = $b->booking_type === 'special_resort' ? 'Full-Resort' : ($b->accommodationUnit->unit_number ?? 'Room/Cottage');
                $cin    = $b->check_in_date?->format('M d, Y') ?? $b->booking_date->format('M d, Y');
                $cout   = $b->check_out_date?->format('M d, Y') ?? 'N/A';
                $status = ucfirst(str_replace('_', ' ', $b->status));
                $pay    = $b->payment ? ucfirst($b->payment->status) : 'Unpaid';
                return "- Ref: {$b->reference_no} | Unit: {$unit} | {$cin} to {$cout} | {$status} | Payment: {$pay} | PHP" . number_format($b->total_amount, 2);
            })->join("\n");
        $paymentData = $myPayments->isEmpty() ? 'No payment records.'
            : $myPayments->map(fn($p) => "- PHP" . number_format($p->amount, 2) . " | " . ucfirst($p->status))->join("\n");
        $name = first_name($user->name);
        $minRoomFmt = number_format($minRoom); $maxRoomFmt = number_format($maxRoom);
        $minCotFmt  = number_format($minCottage); $maxCotFmt = number_format($maxCottage);
        $systemPrompt = "You are the friendly AI assistant for Talisay Beach Resort, speaking with a logged-in TOURIST GUEST named {$name}.\n\n"
            . "YOUR ROLE: Personal resort assistant for this guest. Help with their own bookings, payments, reviews, resort info, and trip planning.\n\n"
            . "THIS GUEST'S LIVE DATA:\nBookings:\n{$bookingData}\n\nRecent Payments:\n{$paymentData}\n\nReviews Submitted: {$myReviews}\n\n"
            . "LIVE RESORT DATA:\n"
            . "- Available Units: {$avail}\n"
            . "- Rooms: PHP{$minRoomFmt} to PHP{$maxRoomFmt} per night | Cottages: PHP{$minCotFmt} to PHP{$maxCotFmt} per night\n"
            . "- Check-In: 2:00 PM | Check-Out: 12:00 PM\n"
            . "- Payment: GCash, Cash on Arrival, Card, PayPal\n"
            . "- Cancellation: 48 hours before check-in | Refunds: 5-7 business days\n"
            . "- Location: Barangay Maslug, Baybay City, Leyte, Philippines\n"
            . "- Contact: +63 (053) 563-7000\n\n"
            . "STRICT RULES:\n"
            . "1. ONLY discuss this guest's OWN data\n"
            . "2. NEVER provide admin-level data (all bookings system-wide, all users, total system revenue, system settings)\n"
            . "3. Address the guest by name ({$name}) occasionally\n"
            . "4. Keep responses concise and friendly\n"
            . "5. If asked about a booking reference not in the list above, say you cannot find it under their account";
        if ($this->gemini->isConfigured()) {
            try {
                $aiResponse = $this->gemini->chat($systemPrompt, $raw, $history);
                return ['response' => $aiResponse, 'intent' => 'ai_response', 'chips' => $this->getDefaultChips('tourist'), 'ai_powered' => true];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Chatbot AI fallback: ' . preg_replace('/key=[^&\s]+/', 'key=redacted', $e->getMessage()));
            }
        }
        return $this->keywordFallbackTourist($message, $raw, $user, $avail, $minRoom, $maxRoom, $minCottage, $maxCottage, $myBookings, $myPayments, $myReviews);
    }

    // STAFF ENGINE
    private function handleStaff(string $message, string $raw, User $user, array $history): array
    {
        if ($this->matchesAny($message, ['delete user', 'delete booking', 'manage user', 'system setting', 'revenue report', 'total revenue', 'financial', 'all payments', 'admin dashboard'])) {
            return ['response' => "That action requires **Administrator** access. Please contact your resort administrator.", 'intent' => 'permission_denied', 'chips' => ["Today's Bookings", 'Check Availability', 'Guest Lookup', 'Contact Admin'], 'ai_powered' => false];
        }
        $today = now()->format('Y-m-d');
        $canSeeBookings = $user->isAdmin() || $user->hasModuleAccess('bookings');
        $todayArrivals = $canSeeBookings
            ? Booking::with(['user', 'accommodationUnit'])
                ->where(function ($q) use ($today) {
                    $q->whereDate('check_in_date', $today)->orWhereDate('booking_date', $today);
                })
                ->orderBy('check_in_date')
                ->take(10)
                ->get()
            : collect();
        $todayDepartures = $canSeeBookings
            ? Booking::with(['user', 'accommodationUnit'])
                ->whereDate('check_out_date', $today)
                ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN])
                ->take(10)
                ->get()
            : collect();
        $pendingCount    = $canSeeBookings ? Booking::where('status', 'pending')->count() : 0;
        $avail           = AccommodationUnit::available()->count();
        $total           = AccommodationUnit::count();
        $cap             = CapacitySchedule::getCapacityForDate($today);
        $maxCap          = $cap ? $cap->max_capacity : (int) SystemSetting::get('daily_visitor_cap', 100);
        $currCount       = $cap ? $cap->current_count : 0;
        $arrivalList     = $todayArrivals->isEmpty() ? 'No arrivals today.'
            : $todayArrivals->map(fn($b) => "- {$b->reference_no} | " . ($b->user->name ?? 'Guest') . " | Unit: " . ($b->accommodationUnit->unit_number ?? 'TBA') . " | " . ucfirst($b->status))->join("\n");
        $departureList   = $todayDepartures->isEmpty() ? 'No departures today.'
            : $todayDepartures->map(fn($b) => "- " . ($b->user->name ?? 'Guest') . " | " . ($b->accommodationUnit->unit_number ?? 'N/A'))->join("\n");
        $staffName = first_name($user->name);
        $systemPrompt = "You are the AI assistant for Talisay Beach Resort, speaking with STAFF MEMBER {$staffName}.\n\n"
            . "YOUR ROLE: Operational assistant for resort staff. Help with daily operations, guest check-ins/check-outs, booking lookups, and availability.\n\n"
            . "LIVE OPERATIONAL DATA FOR TODAY ({$today}):\nToday Arrivals and New Bookings:\n{$arrivalList}\n\nToday Departures:\n{$departureList}\n\n"
            . "Resort Status:\n- Accommodation: {$avail} of {$total} units available\n- Visitor Count Today: {$currCount} of {$maxCap}\n- Pending Bookings: {$pendingCount}\n\n"
            . "YOU CAN HELP WITH: Today check-ins, arrivals, departures; any guest booking lookup by reference number; room availability; guest information; resort operational procedures.\n\n"
            . "STRICT RULES:\n"
            . "1. NEVER perform destructive actions (read-only)\n"
            . "2. NEVER provide system settings, financial summary reports, or user management (admin access required)\n"
            . "3. If asked about admin functions, redirect to contacting the administrator\n"
            . "4. Be efficient and professional, staff need quick clear answers";
        if ($this->gemini->isConfigured()) {
            try {
                $aiResponse = $this->gemini->chat($systemPrompt, $raw, $history);
                return ['response' => $aiResponse, 'intent' => 'ai_response', 'chips' => $this->getDefaultChips('staff'), 'ai_powered' => true];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Chatbot AI fallback: ' . preg_replace('/key=[^&\s]+/', 'key=redacted', $e->getMessage()));
            }
        }
        return $this->keywordFallbackStaff($message, $raw, $user, $avail, $total, $currCount, $maxCap, $pendingCount, $today);
    }

    // ADMIN ENGINE
    private function handleAdmin(string $message, string $raw, User $user, array $history): array
    {
        $totalBookings     = Booking::count();
        $pendingBookings   = Booking::where('status', 'pending')->count();
        $confirmedBookings = Booking::whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN])->count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        $totalUsers        = User::count();
        $adminCount        = User::where('role', 'admin')->count();
        $staffCount        = User::where('role', 'staff')->count();
        $touristCount      = User::where('role', 'tourist')->count();
        $totalRevenue      = Payment::where('status', 'success')->sum('amount');
        $monthRevenue      = Payment::where('status', 'success')->whereMonth('created_at', now()->month)->sum('amount');
        $pendingPayments   = Payment::where('status', 'pending')->count();
        $availUnits        = AccommodationUnit::available()->count();
        $totalUnits        = AccommodationUnit::count();
        $today             = now()->format('Y-m-d');
        $todayBookings     = Booking::whereDate('booking_date', $today)->count();
        $cap               = CapacitySchedule::getCapacityForDate($today);
        $maxCap            = $cap ? $cap->max_capacity : (int) SystemSetting::get('daily_visitor_cap', 100);
        $currCount         = $cap ? $cap->current_count : 0;
        $totalReviews      = Review::count();
        $avgRating         = Review::avg('rating') ?? 0;
        $recentBookings    = Booking::with('user')->orderBy('created_at', 'desc')->take(5)->get()
            ->map(fn($b) => "- {$b->reference_no} | " . ($b->user->name ?? 'Guest') . " | " . ucfirst($b->status) . " | PHP" . number_format($b->total_amount, 2))
            ->join("\n");
        $adminName    = first_name($user->name);
        $totalRevFmt  = number_format($totalRevenue, 2);
        $monthRevFmt  = number_format($monthRevenue, 2);
        $avgRatingFmt = number_format($avgRating, 1);
        $systemPrompt = "You are the intelligent AI management assistant for Talisay Beach Resort, speaking with Admin {$adminName}.\n\n"
            . "YOUR ROLE: Full-access resort management assistant with complete visibility into all resort operations, financials, users, and system data.\n\n"
            . "LIVE RESORT MANAGEMENT DATA:\n"
            . "Booking Overview: Total {$totalBookings} (Today: {$todayBookings}) | Confirmed: {$confirmedBookings} | Pending: {$pendingBookings} | Cancelled: {$cancelledBookings}\n"
            . "Financial: All-Time Revenue PHP{$totalRevFmt} | This Month PHP{$monthRevFmt} | Pending Payments: {$pendingPayments}\n"
            . "Users: Total {$totalUsers} | Admins: {$adminCount} | Staff: {$staffCount} | Tourists: {$touristCount}\n"
            . "Resort Capacity: {$availUnits} of {$totalUnits} units available | Visitor Count Today: {$currCount} of {$maxCap}\n"
            . "Guest Satisfaction: Total Reviews {$totalReviews} | Average Rating {$avgRatingFmt} out of 5.0\n"
            . "Recent Bookings:\n{$recentBookings}\n\n"
            . "GUIDELINES:\n"
            . "- Be analytical and data-driven\n"
            . "- Proactively highlight items needing attention\n"
            . "- Keep responses clear and executive-level\n"
            . "- If asked about a specific booking reference, provide full details from what you know";
        if ($this->gemini->isConfigured()) {
            try {
                $aiResponse = $this->gemini->chat($systemPrompt, $raw, $history);
                return ['response' => $aiResponse, 'intent' => 'ai_response', 'chips' => $this->getDefaultChips('admin'), 'ai_powered' => true];
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Chatbot AI fallback: ' . preg_replace('/key=[^&\s]+/', 'key=redacted', $e->getMessage()));
            }
        }
        return $this->keywordFallbackAdmin($message, $raw, $user, $totalBookings, $pendingBookings, $confirmedBookings, $cancelledBookings, $totalUsers, $adminCount, $staffCount, $touristCount, $totalRevenue, $monthRevenue, $pendingPayments, $availUnits, $totalUnits, $today, $todayBookings, $currCount, $maxCap, $totalReviews, $avgRating);
    }

    // KEYWORD FALLBACK — GUEST
    private function keywordFallbackGuest(string $message, string $raw, $minRoom, $maxRoom, $minCottage, $maxCottage, $avail, $curr, $maxCap, $remaining): array
    {
        $response = null; $intent = 'general'; $chips = $this->getDefaultChips('guest');
        if ($this->matchesAny($message, ['rate', 'price', 'cost', 'how much', 'room', 'cottage', 'cabin', 'accommodation'])) {
            $response = "**Talisay Beach Resort Accommodation Rates:**\n\n* **Rooms:** from PHP" . number_format($minRoom) . " to PHP" . number_format($maxRoom) . " per night\n* **Cottages:** from PHP" . number_format($minCottage) . " to PHP" . number_format($maxCottage) . " per night\n\nReady to book? **Register** or **Login** to make a reservation!";
            $intent = 'rates'; $chips = ['Book Now', 'Check-in Hours', 'Directions', '360 Virtual Tour'];
        } elseif ($this->matchesAny($message, ['availability', 'available', 'slots', 'capacity', 'open'])) {
            $response = "**Live Availability " . now()->format('M d, Y') . ":**\n* Open Accommodations: {$avail} units\n* Visitor Cap: {$curr} of {$maxCap} ({$remaining} slots remaining)\n\nLog in or register to reserve your preferred date!";
            $intent = 'availability'; $chips = ['Room Rates', 'How to Book', 'Register', 'Login'];
        } elseif ($this->matchesAny($message, ['gcash', 'payment', 'pay', 'cash', 'receipt'])) {
            $response = "**Payment Methods:**\n1. GCash: Send to our official number and upload proof\n2. Cash on Arrival: Pay at front desk with booking reference\n3. Credit / Debit Card and PayPal: Secure online gateway\n\nPlease **log in** or **register** first to make a booking!";
            $intent = 'payment'; $chips = ['Register', 'Login', 'Room Rates', 'How to Book'];
        } elseif ($this->matchesAny($message, ['360', 'tour', 'virtual', 'vr', 'panorama'])) {
            $response = "**360 Degree Interactive Virtual Tour:**\nExplore Talisay Beach Resort in immersive 360 degree before you visit!\n\nClick **360 Virtual Tour** in the navigation above to launch it!";
            $intent = 'virtual_tour'; $chips = ['Room Rates', 'Book Now', 'Directions'];
        } elseif ($this->matchesAny($message, ['direction', 'location', 'where', 'address', 'map', 'how to get', 'baybay'])) {
            $response = "**Find Us:**\nTalisay Beach Resort, Barangay Maslug, **Baybay City, Leyte, Philippines**\n\nFrom Tacloban: about 2 hours via Maasin road\nFrom Ormoc City: about 1 hour via Baybay route\nContact: +63 (053) 563-7000";
            $intent = 'directions'; $chips = ['Room Rates', '360 Virtual Tour', 'Register', 'Login'];
        } elseif ($this->matchesAny($message, ['hour', 'time', 'open', 'close', 'check in', 'check out', 'schedule'])) {
            $response = "**Resort Operating Hours:**\n* Day Tour: 7:00 AM to 5:00 PM\n* Check-In: 2:00 PM | Check-Out: 12:00 PM\n* Restaurant: 6:00 AM to 10:00 PM";
            $intent = 'hours'; $chips = ['Room Rates', 'Directions', 'Book Now', '360 Virtual Tour'];
        } elseif ($this->matchesAny($message, ['emergency', 'contact', 'hotline', 'phone'])) {
            $response = "**Resort Contact:**\n* Main: +63 (053) 563-7000\n* Security: Near main entrance, 24/7\n* Emergency: 911 | Baybay PNP: (053) 563-0166";
            $intent = 'contact'; $chips = ['Directions', 'Room Rates', 'Opening Hours'];
        }
        if (!$response) { $dbResult = $this->matchDbIntents($message); if ($dbResult) { $response = $dbResult['response']; $intent = $dbResult['intent']; } }
        if (!$response) {
            if ($this->matchesAny($message, ['tagalog', 'filipino', 'bisaya', 'language', 'dialect', 'hello', 'hi', 'good morning', 'good afternoon', 'kamusta', 'magandang'])) {
                $response = "Hello! 👋 Kumusta! I understand both English and Filipino! I'm your Talisay Beach Resort assistant — I can help you with:\n\n* 🏠 Room & Cottage **Rates**\n* 📅 **Availability** & booking info\n* 📍 **Directions** to the resort\n* 🕐 **Operating Hours**\n* 🌐 **360° Virtual Tour**\n\nWhat would you like to know?";
                $chips = ['Room Rates', 'Directions', '360° Tour', 'Register'];
            } else {
                $response = "I'm your **Talisay Beach Resort** assistant! I can help with room rates, availability, directions, operating hours, and our 360° virtual tour.\n\nFor personal bookings, please **log in** or **register** first. What can I help you with?";
                $chips = $this->getDefaultChips('guest');
            }
        }
        return ['response' => $response, 'intent' => $intent, 'chips' => $chips, 'ai_powered' => false];
    }

    // KEYWORD FALLBACK — TOURIST
    private function keywordFallbackTourist(string $message, string $raw, User $user, $avail, $minRoom, $maxRoom, $minCottage, $maxCottage, $myBookings, $myPayments, $myReviews): array
    {
        $response = null; $intent = 'general'; $chips = $this->getDefaultChips('tourist');
        if (preg_match('/(TBRS?-[A-Z0-9]{4,10})/i', $raw, $matches)) {
            $refNo = strtoupper($matches[1]);
            $booking = Booking::where('reference_no', $refNo)->where('user_id', $user->id)->with(['accommodationUnit', 'payment'])->first();
            if ($booking) {
                $unit = $booking->booking_type === 'special_resort' ? 'Full-Resort' : ($booking->accommodationUnit->unit_number ?? 'Room/Cottage');
                $cin = $booking->check_in_date?->format('M d, Y') ?? $booking->booking_date->format('M d, Y');
                $cout = $booking->check_out_date?->format('M d, Y') ?? 'N/A';
                $status = ucfirst(str_replace('_', ' ', $booking->status));
                $payStatus = $booking->payment ? ucfirst($booking->payment->status) : 'Unpaid';
                $response = "**Booking {$refNo}:**\n* Unit: {$unit}\n* Stay: {$cin} to {$cout} ({$booking->nights_count} night" . ($booking->nights_count > 1 ? 's' : '') . ")\n* Guests: {$booking->guests_count} pax\n* Status: {$status}\n* Payment: {$payStatus} PHP" . number_format($booking->total_amount, 2);
                $intent = 'booking_lookup'; $chips = ['My Bookings', 'Upload Payment Proof', 'Cancellation Policy', 'Contact Support'];
            } else { $response = "I could not find booking **{$refNo}** under your account."; $intent = 'booking_lookup'; $chips = ['My Bookings', 'Contact Support']; }
        } elseif ($this->matchesAny($message, ['my booking', 'my reservation', 'booking history'])) {
            if ($myBookings->isEmpty()) {
                $response = "You do not have any bookings yet, " . first_name($user->name) . "! Tap **Book Now** to explore available rooms and cottages!";
                $chips = ['Check Availability', 'Room Rates', 'Book Now'];
            } else {
                $list = $myBookings->map(fn($b) => "* **" . ($b->reference_no ?? 'N/A') . "** — " . ucfirst(str_replace('_', ' ', $b->status)) . " (" . $b->booking_date->format('M d') . ")")->join("\n");
                $response = "**Your Recent Bookings, " . first_name($user->name) . ":**\n{$list}\n\nFor full details, visit your **My Bookings** page.";
                $chips = ['Check a Booking', 'My Payments', 'Leave a Review', 'Book Again'];
            }
            $intent = 'my_bookings';
        } elseif ($this->matchesAny($message, ['my payment', 'payment status', 'receipt', 'paid'])) {
            if ($myPayments->isEmpty()) { $response = "You do not have any payment records yet."; $chips = ['My Bookings', 'How to Pay', 'Room Rates']; }
            else { $list = $myPayments->map(fn($p) => "* PHP" . number_format($p->amount, 2) . " — " . ucfirst($p->status))->join("\n"); $response = "**Your Recent Payments:**\n{$list}"; $chips = ['My Bookings', 'Contact Support']; }
            $intent = 'my_payments';
        } elseif ($this->matchesAny($message, ['availability', 'available', 'can i book'])) {
            $response = "**Current Availability:** {$avail} units available for reservation right now.\n\nPick your dates in the **Book a Room** section for real-time availability!";
            $intent = 'availability'; $chips = ['Book Now', 'Room Rates', 'My Bookings'];
        } elseif ($this->matchesAny($message, ['tagalog', 'filipino', 'bisaya', 'language', 'hello', 'hi', 'kamusta', 'good morning', 'good afternoon', 'magandang'])) {
            $name = first_name($user->name);
            $response = "Hello, {$name}! 👋 Kumusta! Oo, naiintindihan ko ang Filipino at English! I'm your personal Talisay Beach Resort assistant.\n\nI can help you with your **bookings, payments, availability, and resort info**. What do you need?"; $chips = ['My Bookings', 'Room Rates', 'Check Availability', 'Contact Support'];
        } else {
            $response = "Hi, " . first_name($user->name) . "! I didn't quite understand that — but I can help you with **bookings, payments, availability, and resort information**.\n\nTry asking: *What is my booking status?* or *How many rooms are available?*"; $chips = ['My Bookings', 'Check Availability', 'Room Rates', 'Contact Support'];
        }
        if (!$response) { $dbResult = $this->matchDbIntents($message); if ($dbResult) { $response = $dbResult['response']; $intent = $dbResult['intent']; } }
        return ['response' => $response, 'intent' => $intent, 'chips' => $chips, 'ai_powered' => false];
    }

    // KEYWORD FALLBACK — STAFF
    private function keywordFallbackStaff(string $message, string $raw, User $user, $avail, $total, $currCount, $maxCap, $pendingCount, $today): array
    {
        $response = null; $intent = 'general'; $chips = $this->getDefaultChips('staff');
        if ($this->matchesAny($message, ['today', "today's booking", 'arrivals', 'check-in today'])) {
            $todayBookings = Booking::with(['user', 'accommodationUnit'])->whereDate('booking_date', $today)->orWhereDate('check_in_date', $today)->orderBy('check_in_date')->take(5)->get();
            $count = $todayBookings->count();
            if ($count === 0) { $response = "**Today's Reservations (" . now()->format('M d, Y') . "):** No check-ins scheduled for today."; $chips = ['Check Availability', 'All Bookings', 'Guest Lookup']; }
            else { $list = $todayBookings->map(fn($b) => "* **" . ($b->reference_no ?? 'N/A') . "** — " . ($b->user->name ?? 'Guest') . " at " . ($b->accommodationUnit->unit_number ?? 'TBA'))->join("\n"); $response = "**Today's Reservations ({$count} guests):**\n{$list}"; $chips = ['Check-Out Today', 'Check Availability', 'Guest Lookup']; }
            $intent = 'staff_reservations';
        } else        if (preg_match('/(TBRS?-[A-Z0-9]{4,10})/i', $raw, $matches)) {
            if (!$user->isAdmin() && !$user->hasPermission('bookings', 'view')) {
                return ['response' => 'Booking lookups require the Bookings permission. Please contact the administrator if you need access.', 'intent' => 'permission_denied', 'chips' => $chips, 'ai_powered' => false];
            }
            $refNo = strtoupper($matches[1]);
            $booking = Booking::where('reference_no', $refNo)->with(['user', 'accommodationUnit', 'payment'])->first();
            if ($booking) {
                $unit = $booking->booking_type === 'special_resort' ? 'Full-Resort' : ($booking->accommodationUnit->unit_number ?? 'TBA');
                $status = ucfirst(str_replace('_', ' ', $booking->status));
                $pay = $booking->payment ? ucfirst($booking->payment->status) : 'Unpaid';
                $cin = $booking->check_in_date?->format('M d, Y') ?? 'Not set';
                $response = "**Reservation {$refNo}:**\n* Guest: " . ($booking->user->name ?? 'N/A') . "\n* Unit: {$unit} | Check-In: {$cin}\n* Guests: {$booking->guests_count} pax | Status: {$status}\n* Payment: {$pay} PHP" . number_format($booking->total_amount, 2);
                $chips = ['Confirm Check-In', 'Guest Lookup', "Today's Bookings"];
            } else { $response = "No booking found for **{$refNo}**."; $chips = ["Today's Bookings", 'Guest Lookup']; }
            $intent = 'staff_booking_lookup';
        } elseif ($this->matchesAny($message, ['availability', 'available', 'vacant', 'capacity'])) {
            $response = "**Current Resort Status:**\n* Accommodation: {$avail} of {$total} units available\n* Visitor Count Today: {$currCount} of {$maxCap}\n* Pending Bookings: {$pendingCount}";
            $intent = 'availability'; $chips = ["Today's Bookings", 'All Bookings', 'Room Status'];
        } elseif ($this->matchesAny($message, ['tagalog', 'filipino', 'bisaya', 'language', 'hello', 'hi', 'kamusta', 'good morning', 'good afternoon', 'magandang'])) {
            $name = first_name($user->name);
            $response = "Hello, {$name}! 👋 Kumusta! I understand both English and Filipino! As **Resort Staff**, I can help with:\n\n* Today's check-ins and departures\n* Guest and booking lookups (enter a reference no.)\n* Room and cottage availability\n* Pending bookings count\n\nWhat do you need?"; $chips = ["Today's Arrivals", "Pending Bookings", "Check Availability", "Guest Lookup"];
        } else {
            $name = first_name($user->name);
            $response = "Hello, {$name}! I didn't recognize that question, but as **Resort Staff** I can help with:\n\n* Today's reservations and arrivals\n* Booking lookups by reference number\n* Room and capacity status\n\nEnter a **booking reference number** or ask about today's schedule!"; $chips = ["Today's Arrivals", "Check Availability", "Guest Lookup", "Pending Bookings"];
        }
        return ['response' => $response, 'intent' => $intent, 'chips' => $chips, 'ai_powered' => false];
    }

    // KEYWORD FALLBACK — ADMIN
    private function keywordFallbackAdmin(string $message, string $raw, User $user, $totalBookings, $pendingBookings, $confirmedBookings, $cancelledBookings, $totalUsers, $adminCount, $staffCount, $touristCount, $totalRevenue, $monthRevenue, $pendingPayments, $availUnits, $totalUnits, $today, $todayBookings, $currCount, $maxCap, $totalReviews, $avgRating): array
    {
        $response = null; $intent = 'general'; $chips = $this->getDefaultChips('admin');
        if ($this->matchesAny($message, ['summary', 'dashboard', 'overview', 'status', 'quick stats'])) {
            $response = "**Resort Dashboard Summary:**\n* Total Bookings: " . number_format($totalBookings) . " ({$pendingBookings} pending)\n* Today New Bookings: {$todayBookings}\n* Total Users: " . number_format($totalUsers) . "\n* Available Units: {$availUnits}\n* Total Revenue: PHP" . number_format($totalRevenue, 2);
            $intent = 'admin_summary'; $chips = ['Pending Bookings', 'Revenue Report', 'User Count', 'Availability'];
        } elseif ($this->matchesAny($message, ['revenue', 'income', 'earnings', 'financial'])) {
            $response = "**Revenue Summary:**\n* All-Time Revenue: PHP" . number_format($totalRevenue, 2) . "\n* This Month: PHP" . number_format($monthRevenue, 2) . "\n* Pending Payments: {$pendingPayments} transactions";
            $intent = 'admin_revenue'; $chips = ['Full Report', 'Pending Payments', 'Booking Stats', 'User Count'];
        } elseif ($this->matchesAny($message, ['booking', 'reservation', 'all booking'])) {
            $response = "**Booking Overview:**\n* Total: " . number_format($totalBookings) . " | Today: {$todayBookings}\n* Confirmed: {$confirmedBookings} | Pending: {$pendingBookings} | Cancelled: {$cancelledBookings}";
            $intent = 'admin_bookings'; $chips = ['Pending Bookings', 'Revenue Report', 'User Count'];
        } elseif ($this->matchesAny($message, ['user', 'users', 'accounts', 'tourist count'])) {
            $response = "**User Accounts:**\n* Total Accounts: " . number_format($totalUsers) . "\n* Admins: {$adminCount} | Staff: {$staffCount} | Tourists: {$touristCount}";
            $intent = 'admin_users'; $chips = ['Add User', 'Booking Stats', 'Revenue Report', 'Dashboard'];
        } elseif ($this->matchesAny($message, ['availability', 'available', 'units', 'capacity'])) {
            $response = "**Resort Capacity Status:**\n* Accommodation: {$availUnits} of {$totalUnits} units available\n* Daily Visitor Count: {$currCount} of {$maxCap}";
            $intent = 'admin_availability'; $chips = ['Booking Stats', 'Revenue Report', 'User Count'];
        } elseif ($this->matchesAny($message, ['review', 'feedback', 'rating', 'satisfaction'])) {
            $response = "**Guest Reviews:**\n* Total Reviews: " . number_format($totalReviews) . "\n* Average Rating: " . number_format($avgRating, 1) . " out of 5.0";
            $intent = 'admin_reviews'; $chips = ['Dashboard', 'Revenue Report', 'Booking Stats'];
        } elseif ($this->matchesAny($message, ['tagalog', 'filipino', 'bisaya', 'language', 'hello', 'hi', 'kamusta', 'good morning', 'good afternoon', 'magandang'])) {
            $name = first_name($user->name);
            $response = "Hello, Admin {$name}! 👋 Kumusta! Oo, naiintindihan ko ang Filipino at English!\n\nAs your **resort management assistant**, I have full access to:\n* Booking stats and recent reservations\n* Revenue and payment summaries\n* User account counts\n* Resort availability and capacity\n* Guest reviews and ratings\n\nWhat data do you need?"; $chips = ['Dashboard Summary', 'Revenue Report', 'Booking Stats', 'User Count'];
        } else {
            $name = first_name($user->name);
            $response = "Admin {$name}, I didn't quite understand that question. I can help you with **resort management data** including:\n\n* 📊 Dashboard summary and stats\n* 💰 Revenue and payment reports\n* 📅 Booking overviews\n* 👥 User account counts\n* 🏠 Room availability and capacity\n* ⭐ Guest reviews\n\nWhat would you like to check?"; $chips = ['Dashboard Summary', 'Revenue Report', 'Booking Stats', 'Availability'];
        }
        return ['response' => $response, 'intent' => $intent, 'chips' => $chips, 'ai_powered' => false];
    }

    // SHARED UTILITIES
    private function matchDbIntents(string $message): ?array
    {
        $intents = ChatbotIntent::active()->get();
        foreach ($intents as $intent) {
            $keywords = array_map('trim', explode(',', strtolower($intent->keyword)));
            foreach ($keywords as $kw) {
                if (empty($kw)) continue;
                if (str_contains($message, $kw) || $this->fuzzyMatch($message, $kw)) {
                    return ['response' => $intent->response, 'intent' => $intent->category];
                }
            }
        }
        return null;
    }

    private function matchesAny(string $message, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($message, strtolower($needle))) return true;
        }
        return false;
    }

    private function fuzzyMatch(string $input, string $keyword): bool
    {
        if (strlen($keyword) < 3) return false;
        similar_text($input, $keyword, $percent);
        return $percent > 75;
    }

    private function getWelcomeMessage(string $role, ?User $user): string
    {
        $name = $user ? first_name($user->name) : '';
        return match ($role) {
            'tourist' => "Welcome back, **{$name}**! I am your AI-powered resort assistant. I can help with your bookings, activities, recommendations, payments, and trip planning.",
            'staff'   => "Hello, **{$name}**! I am your AI staff assistant. Ask me about today's reservations, guest lookups, and resort operations.",
            'admin'   => "Welcome, **Admin {$name}**! I am your AI management assistant with full access to resort analytics, bookings, users, staff, reports, and operations.",
            default   => "Hello! I am your **Talisay Beach Resort** AI assistant. Ask me anything about our rooms, packages, activities, facilities, and resort information!",
        };
    }

    private function getDefaultChips(string $role): array
    {
        if ($role === 'guest') {
            $customChips = SystemSetting::get('chatbot_chips');
            if (!empty($customChips)) {
                $chips = array_filter(array_map('trim', explode(',', $customChips)));
                if (!empty($chips)) {
                    return array_values($chips);
                }
            }
        }
        return match ($role) {
            'tourist' => ['My Bookings', 'Check Availability', 'Room Rates', '360 Virtual Tour', 'My Payments'],
            'staff'   => ["Today's Bookings", 'Check Availability', 'Guest Lookup', 'Check-Out Today'],
            'admin'   => ['Dashboard Summary', 'Booking Stats', 'Revenue Report', 'User Count', 'Availability'],
            default   => ['Room Rates', 'Cottage Rates', 'Day Tour Slots', 'Check-in Times', 'Amenities', 'Directions'],
        };
    }

    private function getModeLabel(string $role): string
    {
        return match ($role) {
            'tourist' => 'Tourist Mode',
            'staff'   => 'Staff Mode',
            'admin'   => 'Admin Mode',
            default   => 'Guest Mode',
        };
    }

    private function getModeColor(string $role): string
    {
        return match ($role) {
            'tourist' => 'emerald',
            'staff'   => 'amber',
            'admin'   => 'rose',
            default   => 'sky',
        };
    }
}
