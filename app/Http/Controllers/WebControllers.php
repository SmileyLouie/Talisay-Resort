<?php

namespace App\Http\Controllers;

// ============================================================
// WebControllers.php — Central Application Logic Engine
// ============================================================
// Contains all primary business logic and web request handlers.
// Individual controller classes inherit from WebControllers.
// ============================================================

use App\Events\BookingUpdated;
use App\Events\CapacityUpdated;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\ChatbotConfig;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use App\Models\AccommodationUnit;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\TourAsset;
use App\Models\User;
use App\Services\ChatbotAIService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;

class WebControllers extends Controller
{
    // ─── Dashboard Logic ──────────────────────────────────────
    public function dashboardIndex()
    {
        $today = now()->format('Y-m-d');
        $todaysBookings = Booking::whereDate('booking_date', $today)->count();
        $capacity = CapacitySchedule::getCapacityForDate($today);
        $totalUnits = AccommodationUnit::count();
        $activeUnits = AccommodationUnit::available()->count();
        $monthStart = now()->startOfMonth();
        $monthlyRevenue = Payment::where('status', 'success')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        $recentBookings = Booking::with(['user', 'accommodationUnit'])
            ->orderBy('created_at', 'desc')
            ->take(10)->get();

        $occupancyData = CapacitySchedule::whereBetween('date', [now()->subDays(6), now()])
            ->orderBy('date')->get()
            ->map(fn($c) => [
                'date' => $c->date->format('M d'),
                'utilization' => $c->getUtilizationPercent(),
            ]);

        $upcomingReservations = Booking::with(['user', 'accommodationUnit'])
            ->where('booking_date', '>=', $today)
            ->whereIn('status', ['paid', 'pending', 'checked_in'])
            ->orderBy('booking_date')
            ->take(8)->get();

        $recentReviews = Review::with(['user', 'booking.accommodationUnit'])
            ->orderBy('created_at', 'desc')
            ->take(5)->get();

        $driver = DB::getDriverName();
        $select = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
            : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total";

        $revenueTrends = Payment::where('status', 'success')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw($select)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $popularAccommodations = AccommodationUnit::withCount(['bookings' => fn($q) =>
            $q->whereIn('status', ['paid', 'checked_in', 'completed'])
        ])->orderBy('bookings_count', 'desc')->take(5)->get();

        $approvedReviews = Review::approved()->get();
        $sentimentData = [
            'positive' => $approvedReviews->where('rating', '>=', 4)->count(),
            'neutral'  => $approvedReviews->where('rating', 3)->count(),
            'negative' => $approvedReviews->where('rating', '<=', 2)->count(),
        ];

        $quickStats = [
            'totalBookings'     => Booking::count(),
            'pendingBookings'   => Booking::where('status', 'pending')->count(),
            'checkedInGuests'   => Booking::where('status', 'checked_in')->sum('guests_count'),
            'totalRevenue'      => Payment::where('status', 'success')->sum('amount'),
            'totalUsers'        => User::count(),
            'activeUnits'       => $activeUnits,
            'totalUnits'        => $totalUnits,
            'pendingReviews'    => Review::where('is_approved', false)->count(),
        ];

        return view('dashboard.index', compact(
            'todaysBookings', 'capacity', 'totalUnits', 'activeUnits',
            'monthlyRevenue', 'recentBookings', 'occupancyData',
            'upcomingReservations',
            'recentReviews', 'quickStats', 'revenueTrends',
            'popularAccommodations', 'sentimentData'
        ));
    }

    // ─── Authentication Logic ─────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string',
            'password' => 'required',
        ]);

        $loginInput = trim($request->input('email'));
        $targetEmail = $loginInput;

        $userLookup = User::where('email', $loginInput)
            ->orWhere('name', $loginInput)
            ->orWhere('staff_id', $loginInput)
            ->first();

        if ($userLookup) {
            $targetEmail = $userLookup->email;
        }

        $credentials = [
            'email'    => $targetEmail,
            'password' => $request->input('password'),
        ];

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                return back()->withErrors(['email' => 'Your account has been deactivated. Please contact support.']);
            }

            AuditLog::log('user_login', $user);

            if ($user->isAdmin())   return redirect()->intended(route('admin.dashboard'));
            if ($user->isStaff())   return redirect()->intended(route('staff.dashboard'));
            if ($user->isTourist()) return redirect()->intended(route('tourist.dashboard'));

            return redirect()->intended('/');
        }

        return back()->withErrors(['email' => 'The provided credentials do not match our records.'])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        $user = Auth::user();

        // Capture the portal the user belongs to BEFORE logging them out
        $isAdminOrStaff = $user && ($user->isAdmin() || $user->isStaff());

        if ($user) {
            AuditLog::log('user_logout', $user);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirect admin/staff back to the admin portal login page
        if ($isAdminOrStaff) {
            return redirect()->route('login', ['portal' => 'admin']);
        }

        // Tourists go to the public home/landing page
        return redirect()->route('home');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        // Support both first_name + last_name or single name
        if (!$request->filled('name') && ($request->filled('first_name') || $request->filled('last_name'))) {
            $request->merge([
                'name' => trim($request->input('first_name', '') . ' ' . $request->input('last_name', ''))
            ]);
        }

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'avatar'   => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'tourist',
            'avatar'   => $avatarPath,
        ]);

        Auth::login($user);
        AuditLog::log('user_registered', $user);

        return redirect()->route('tourist.dashboard')->with('success', 'Welcome to Talisay Beach Resort!');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $status = Password::sendResetLink($request->only('email'));

        return $status === Password::RESET_LINK_SENT
            ? back()->with('status', __($status))
            : back()->withErrors(['email' => __($status)]);
    }

    public function showResetPassword(Request $request, string $token)
    {
        return view('auth.reset-password', ['request' => $request]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('login')->with('status', __($status))
            : back()->withErrors(['email' => [__($status)]]);
    }

    // ─── Booking Logic ────────────────────────────────────────
    public function bookingsIndex(Request $request)
    {
        $query = Booking::with(['user', 'accommodationUnit', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhere('guest_name_manual', 'like', "%{$search}%")
                  ->orWhere('guest_contact_manual', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(15);
        $tourists = User::where('role', 'tourist')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $accommodationUnits = AccommodationUnit::where('is_available', true)->orderBy('unit_number')->get();

        return view('bookings.index', compact('bookings', 'tourists', 'accommodationUnits'));
    }

    public function manualBookingStore(Request $request)
    {
        $request->validate([
            'guest_type'            => 'required|in:registered,walk_in',
            'user_id'               => 'required_if:guest_type,registered|nullable|exists:users,id',
            'guest_name'            => 'required_if:guest_type,walk_in|nullable|string|max:150',
            'guest_contact'         => 'required_if:guest_type,walk_in|nullable|string|max:100',
            'accommodation_unit_id' => 'required|exists:accommodation_units,id',
            'booking_date'          => 'required|date',
            'check_out_date'        => 'nullable|date|after:booking_date',
            'guests_count'          => 'required|integer|min:1',
            'payment_method'        => 'required|in:cash,gcash,card,paypal',
            'initial_status'        => 'required|in:pending,paid,checked_in',
            'special_requests'      => 'nullable|string|max:500',
        ]);

        $unit = AccommodationUnit::findOrFail($request->accommodation_unit_id);

        if (!$unit->is_available) {
            return back()->with('error', "{$unit->unit_number} is currently marked unavailable by management.")->withInput();
        }

        if ($request->guests_count > $unit->max_occupancy) {
            return back()->with('error', "Guests count exceeds capacity limit of {$unit->max_occupancy} guests for {$unit->unit_number}.")->withInput();
        }

        $bookingDate  = $request->booking_date;
        $checkOutDate = $request->check_out_date ?? \Carbon\Carbon::parse($bookingDate)->addDay()->format('Y-m-d');
        $nights = (int) \Carbon\Carbon::parse($bookingDate)->diffInDays(\Carbon\Carbon::parse($checkOutDate));
        if ($nights < 1) $nights = 1;

        // Conflict check
        $conflict = Booking::getConflictingBooking($unit->id, $bookingDate, $checkOutDate);
        if ($conflict) {
            $conflictIn  = $conflict->check_in_date ? $conflict->check_in_date->format('M d, Y') : $conflict->booking_date->format('M d, Y');
            $conflictOut = $conflict->check_out_date ? $conflict->check_out_date->format('M d, Y') : \Carbon\Carbon::parse($conflictIn)->addDay()->format('M d, Y');

            if ($conflict->booking_type === 'special_resort') {
                return back()->with('error', "Reservation conflict: An exclusive Full-Resort booking is active from {$conflictIn} to {$conflictOut}.")->withInput();
            }

            return back()->with('error', "Reservation conflict: {$unit->unit_number} is already booked from {$conflictIn} to {$conflictOut} (Ref: {$conflict->reference_no}).")->withInput();
        }

        $totalAmount = $unit->price_per_night * $nights;
        $status = $request->initial_status;
        $isRegistered = $request->guest_type === 'registered';

        $userId = $isRegistered ? $request->user_id : null;
        $guestNameManual = $isRegistered ? null : $request->guest_name;
        $guestContactManual = $isRegistered ? null : $request->guest_contact;

        $booking = Booking::create([
            'reference_no'          => Booking::generateReferenceNo(),
            'booking_type'          => 'regular',
            'booking_source'        => 'manual',
            'user_id'               => $userId,
            'guest_name_manual'     => $guestNameManual,
            'guest_contact_manual'  => $guestContactManual,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => $bookingDate,
            'check_in_date'         => $bookingDate,
            'check_out_date'        => $checkOutDate,
            'nights_count'          => $nights,
            'time_slot'             => '2:00 PM Check-in · 11:00 AM Check-out',
            'guests_count'          => $request->guests_count,
            'status'                => $status,
            'total_amount'          => $totalAmount,
            'special_requests'      => $request->special_requests,
        ]);

        $paymentMethod = $request->payment_method;
        $isCash = $paymentMethod === 'cash';
        $paymentStatus = ($status === 'paid' || $status === 'checked_in') ? 'success' : 'pending';

        Payment::create([
            'booking_id'         => $booking->id,
            'amount'             => $totalAmount,
            'gateway'            => $paymentMethod,
            'payment_channel'    => $paymentMethod,
            'transaction_id'     => $isCash ? ('CASH-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8))) : ('MNL-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8))),
            'status'             => $paymentStatus,
            'is_cash_on_arrival' => $isCash,
            'metadata'           => ['source' => 'manual', 'created_by' => Auth::id()],
        ]);

        $capacity = CapacitySchedule::getCapacityForDate($bookingDate);
        $capacity->increment('current_count', $request->guests_count);

        AuditLog::log('manual_booking_created', $booking, null, [
            'created_by' => Auth::id(),
            'guest_type' => $request->guest_type,
            'booking'    => $booking->toArray(),
        ]);

        if ($userId) {
            \App\Models\NotificationModel::notifyUser(
                $userId,
                'booking',
                'Reservation Created by Front Desk',
                "A reservation for {$unit->unit_number} ({$booking->reference_no}) was created for you by our resort staff.",
                ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
            );
        }

        return redirect()->route('bookings.index')->with('success', "Manual booking {$booking->reference_no} created successfully for {$booking->guest_name}!");
    }

    public function updateBookingStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status'              => 'required|in:pending,paid,checked_in,checked_out,cancelled,completed',
            'cancellation_reason' => 'required_if:status,cancelled',
        ]);

        $oldStatus = $booking->status;

        if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
            $booking->update([
                'status'              => 'cancelled',
                'cancelled_at'        => now(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            $capacity = CapacitySchedule::getCapacityForDate($booking->booking_date);
            $capacity->decrement('current_count', max(0, $booking->guests_count));
            broadcast(new CapacityUpdated($capacity));

        } elseif ($oldStatus === 'cancelled' && $request->status !== 'cancelled') {
            $booking->update($request->only('status'));

            $capacity = CapacitySchedule::getCapacityForDate($booking->booking_date);
            $capacity->increment('current_count', $booking->guests_count);
            broadcast(new CapacityUpdated($capacity));

        } else {
            $booking->update($request->only('status'));
        }

        AuditLog::log('booking_status_changed', $booking, ['status' => $oldStatus], ['status' => $booking->status]);
        broadcast(new BookingUpdated($booking));

        // Notify tourist
        \App\Models\NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Status Updated',
            "Your reservation {$booking->reference_no} status has been updated to: " . ucfirst(str_replace('_', ' ', $booking->status)) . ".",
            ['booking_id' => $booking->id, 'status' => $booking->status]
        );



        return back()->with('success', 'Booking status updated successfully.');
    }

    public function approveSpecialResortBooking(Request $request, Booking $booking)
    {
        if ($booking->booking_type !== 'special_resort') {
            return back()->with('error', 'This is not a special full-resort booking.');
        }

        DB::transaction(function () use ($booking, $request) {
            $booking->update([
                'admin_approval_status' => 'approved',
                'admin_approved_by'     => Auth::id(),
                'status'                => 'paid',
            ]);

            if ($booking->payment) {
                $booking->payment->update(['status' => 'success']);
            }

            // Lock all resort capacity for each date in the range
            $startDate = \Carbon\Carbon::parse($booking->check_in_date ?? $booking->booking_date);
            $endDate = \Carbon\Carbon::parse($booking->check_out_date ?? $booking->booking_date);

            for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
                $capacity = CapacitySchedule::getCapacityForDate($date->format('Y-m-d'));
                $capacity->update([
                    'current_count' => $capacity->max_capacity, // Lock full capacity
                ]);
                broadcast(new CapacityUpdated($capacity));
            }

            // Send notification to tourist
            \App\Models\NotificationModel::create([
                'user_id' => $booking->user_id,
                'type'    => 'booking',
                'title'   => 'Special Resort Booking Approved',
                'message' => "Your exclusive full-resort booking ({$booking->reference_no}) has been approved by the Administrator. We look forward to hosting your exclusive event!",
                'data'    => ['booking_id' => $booking->id],
            ]);

            AuditLog::log('special_resort_booking_approved', $booking, null, $booking->toArray());
        });

        return back()->with('success', "Special Full-Resort Booking {$booking->reference_no} has been APPROVED! Resort inventory locked for the selected dates.");
    }

    public function rejectSpecialResortBooking(Request $request, Booking $booking)
    {
        if ($booking->booking_type !== 'special_resort') {
            return back()->with('error', 'This is not a special full-resort booking.');
        }

        $reason = $request->reason ?? 'Special resort booking request was declined by management.';

        DB::transaction(function () use ($booking, $reason) {
            $booking->update([
                'admin_approval_status' => 'rejected',
                'admin_approved_by'     => Auth::id(),
                'status'                => 'cancelled',
                'cancelled_at'          => now(),
                'cancellation_reason'   => $reason,
            ]);

            if ($booking->payment) {
                $booking->payment->update(['status' => 'failed']);
            }

            // Send notification to tourist
            \App\Models\NotificationModel::create([
                'user_id' => $booking->user_id,
                'type'    => 'booking',
                'title'   => 'Special Resort Booking Update',
                'message' => "Your special full-resort booking ({$booking->reference_no}) could not be accommodated: {$reason}",
                'data'    => ['booking_id' => $booking->id],
            ]);

            AuditLog::log('special_resort_booking_rejected', $booking, null, ['reason' => $reason]);
        });

        return back()->with('success', "Special Booking {$booking->reference_no} has been rejected.");
    }

    // ─── Payment Logic ────────────────────────────────────────
    public function paymentsIndex(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.accommodationUnit']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('gateway')) {
            $query->where('gateway', $request->gateway);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                  ->orWhereHas('booking', fn($bq) => $bq->where('reference_no', 'like', "%{$search}%"));
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('payments.index', compact('payments'));
    }

    public function paymentShow(Payment $payment)
    {
        $payment->load(['booking.user', 'booking.accommodationUnit']);
        return view('payments.show', compact('payment'));
    }

    public function approvePayment(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Payment is not in pending status.');
        }

        $payment->update(['status' => 'success']);
        $payment->booking->update(['status' => 'paid']);

        // Notify the tourist that their payment has been confirmed
        \App\Models\NotificationModel::create([
            'user_id' => $payment->booking->user_id,
            'type'    => 'payment',
            'title'   => 'Payment Confirmed',
            'message' => 'Your payment of ₱' . number_format($payment->amount, 2) . ' for booking ' . $payment->booking->reference_no . ' has been confirmed. Your reservation is now active!',
            'data'    => ['booking_id' => $payment->booking_id],
        ]);

        AuditLog::log('payment_approved', $payment);

        return back()->with('success', 'Payment approved successfully.');
    }

    public function rejectPayment(Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        AuditLog::log('payment_rejected', $payment);

        // Notify tourist
        \App\Models\NotificationModel::notifyUser(
            $payment->booking->user_id,
            'payment',
            'Payment Proof Verification Issue',
            "Your payment proof for booking {$payment->booking->reference_no} could not be verified. Please re-upload a clear receipt or contact front desk.",
            ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
        );

        return back()->with('success', 'Payment rejected.');
    }



    // ─── AI Chatbot Operations & Knowledge Base ───────────────
    public function chatbotIndex()
    {
        $logs              = ChatbotLog::with('user')->latest()->paginate(15);
        $totalInteractions = ChatbotLog::count();
        $todayInteractions = ChatbotLog::whereDate('created_at', today())->count();
        $geminiConfigured  = app(\App\Services\GeminiAIService::class)->isConfigured();
        $geminiModel       = config('services.gemini.model', 'gemini-3.6-flash');

        $persona      = SystemSetting::get('chatbot_persona', 'You are the friendly, welcoming, and knowledgeable AI resort concierge for Talisay Beach Resort in Baybay City, Leyte, Philippines.');
        $customRules  = SystemSetting::get('chatbot_custom_rules', "Check-in time is 2:00 PM, Check-out is 12:00 PM. Day tour hours are from 7:00 AM to 5:00 PM. Oceanview restaurant operates from 6:00 AM to 10:00 PM. Swimming pool is open daily from 7:00 AM to 9:00 PM. Free parking available. Pets allowed in designated open cottage areas with leash.");
        $announcement = SystemSetting::get('chatbot_announcement', 'Welcome to Talisay Beach Resort! Online reservations and interactive 360 Virtual Tour are now available on our website.');
        $quickChips   = SystemSetting::get('chatbot_chips', 'Room Rates, Cottage Rates, Day Tour Slots, Check-in Times, Amenities, How to Book, Directions');

        $minRoom        = AccommodationUnit::rooms()->min('price_per_night') ?? 1500;
        $maxRoom        = AccommodationUnit::rooms()->max('price_per_night') ?? 3200;
        $minCottage     = AccommodationUnit::cottages()->min('price_per_night') ?? 2000;
        $maxCottage     = AccommodationUnit::cottages()->max('price_per_night') ?? 4500;
        $availableUnits = AccommodationUnit::available()->count();
        $totalUnits     = AccommodationUnit::count();

        $today          = now()->format('Y-m-d');
        $cap            = CapacitySchedule::getCapacityForDate($today);
        $maxCap         = $cap ? $cap->max_capacity : (int) SystemSetting::get('daily_visitor_cap', 100);
        $currCap        = $cap ? $cap->current_count : 0;
        $remainingSlots = max(0, $maxCap - $currCap);

        return view('chatbot.index', compact(
            'logs',
            'totalInteractions',
            'todayInteractions',
            'geminiConfigured',
            'geminiModel',
            'persona',
            'customRules',
            'announcement',
            'quickChips',
            'minRoom',
            'maxRoom',
            'minCottage',
            'maxCottage',
            'availableUnits',
            'totalUnits',
            'maxCap',
            'currCap',
            'remainingSlots'
        ));
    }

    public function chatbotUpdateSettings(Request $request)
    {
        $request->validate([
            'chatbot_persona'      => 'nullable|string|max:1000',
            'chatbot_custom_rules' => 'nullable|string|max:3000',
            'chatbot_announcement' => 'nullable|string|max:1000',
            'chatbot_chips'        => 'nullable|string|max:500',
        ]);

        SystemSetting::set('chatbot_persona', trim($request->input('chatbot_persona', '')));
        SystemSetting::set('chatbot_custom_rules', trim($request->input('chatbot_custom_rules', '')));
        SystemSetting::set('chatbot_announcement', trim($request->input('chatbot_announcement', '')));
        SystemSetting::set('chatbot_chips', trim($request->input('chatbot_chips', '')));

        return redirect()->route('chatbot.index')->with('success', 'AI Chatbot knowledge base and instructions updated successfully.');
    }

    public function chatbotClearLogs()
    {
        ChatbotLog::truncate();
        return redirect()->route('chatbot.index')->with('success', 'Chatbot conversation logs cleared successfully.');
    }

    public function chatbotStore(Request $request)
    {
        $request->validate([
            'intent_name' => 'required|string|max:100|unique:chatbot_intents,intent_name',
            'category'    => 'required|string|max:100',
            'keywords'    => 'required|string',
            'response'    => 'required|string',
        ]);

        $keywords = array_filter(array_map('trim', explode(',', $request->keywords)));

        $intent = ChatbotIntent::create([
            'intent_name' => $request->intent_name,
            'category'    => $request->category,
            'keywords'    => $keywords,
            'response'    => $request->response,
            'is_active'   => true,
        ]);

        AuditLog::log('chatbot_intent_created', $intent, null, $intent->toArray());

        return redirect()->route('chatbot.index')->with('success', 'Intent created successfully.');
    }

    public function chatbotUpdate(Request $request, ChatbotIntent $intent)
    {
        $request->validate([
            'intent_name' => 'required|string|max:100|unique:chatbot_intents,intent_name,' . $intent->id,
            'category'    => 'required|string|max:100',
            'keywords'    => 'required|string',
            'response'    => 'required|string',
            'is_active'   => 'boolean',
        ]);

        $oldValues = $intent->toArray();
        $keywords  = array_filter(array_map('trim', explode(',', $request->keywords)));

        $intent->update([
            'intent_name' => $request->intent_name,
            'category'    => $request->category,
            'keywords'    => $keywords,
            'response'    => $request->response,
            'is_active'   => $request->boolean('is_active', true),
        ]);

        AuditLog::log('chatbot_intent_updated', $intent, $oldValues, $intent->toArray());

        return redirect()->route('chatbot.index')->with('success', 'Intent updated successfully.');
    }

    public function chatbotDestroy(ChatbotIntent $intent)
    {
        $oldValues = $intent->toArray();
        $intent->delete();
        AuditLog::log('chatbot_intent_deleted', null, $oldValues, null);

        return redirect()->route('chatbot.index')->with('success', 'Intent deleted successfully.');
    }

    // ─── Tour Logic ───────────────────────────────────────────
    public function tourIndex()
    {
        $assets = TourAsset::orderBy('sort_order')->paginate(20);
        return view('tour.index', compact('assets'));
    }

    public function tourViewer()
    {
        $assets = TourAsset::active()->get();
        return view('tour.viewer', compact('assets'));
    }

    public function tourStore(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'panorama'    => 'required|file|image|max:10240',
            'type'        => 'required|in:image,video',
            'sort_order'  => 'required|integer',
        ]);

        if ($request->hasFile('panorama')) {
            $path = $request->file('panorama')->store('tour-assets', 'public');

            $asset = TourAsset::create([
                'title'         => $request->title,
                'description'   => $request->description,
                'panorama_path' => $path,
                'type'          => $request->type,
                'hotspots'      => [],
                'sort_order'    => $request->sort_order,
                'is_active'     => true,
            ]);

            AuditLog::log('tour_asset_uploaded', $asset, null, $asset->toArray());

            return back()->with('success', 'Tour asset uploaded successfully.');
        }

        return back()->with('error', 'Failed to upload panorama file.');
    }

    public function tourUpdate(Request $request, TourAsset $asset)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'panorama'    => 'nullable|file|max:20480',
            'type'        => 'required|in:image,video',
            'sort_order'  => 'required|integer',
            'is_active'   => 'nullable',
        ]);

        $oldValues = $asset->toArray();

        $data = [
            'title'       => $request->title,
            'description' => $request->description,
            'type'        => $request->type,
            'sort_order'  => $request->sort_order,
            'is_active'   => $request->has('is_active') ? (bool)$request->is_active : false,
        ];

        if ($request->hasFile('panorama')) {
            if ($asset->panorama_path && Storage::disk('public')->exists($asset->panorama_path)) {
                Storage::disk('public')->delete($asset->panorama_path);
            }
            $data['panorama_path'] = $request->file('panorama')->store('tour-assets', 'public');
        }

        $asset->update($data);

        AuditLog::log('tour_asset_updated', $asset, $oldValues, $asset->toArray());

        return back()->with('success', 'Tour scene asset updated successfully.');
    }

    public function tourDestroy(TourAsset $asset)
    {
        $oldValues = $asset->toArray();

        if ($asset->panorama_path) {
            Storage::disk('public')->delete($asset->panorama_path);
        }

        $asset->delete();
        AuditLog::log('tour_asset_deleted', null, $oldValues, null);

        return back()->with('success', 'Tour asset deleted successfully.');
    }

    // ─── Review Logic ─────────────────────────────────────────
    public function reviewsIndex(Request $request)
    {
        $query = Review::with(['user', 'booking.accommodationUnit']);

        if ($request->filled('comment_status')) {
            if ($request->comment_status === 'blocked') {
                $query->where('is_comment_blocked', true);
            } elseif ($request->comment_status === 'active') {
                $query->where('is_comment_blocked', false);
            }
        } elseif ($request->filled('status')) {
            // Legacy filter support
            if ($request->status === 'blocked') {
                $query->where('is_comment_blocked', true);
            }
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->rating);
        }

        $totalReviews          = Review::count();
        $activeCommentsCount   = Review::where('is_comment_blocked', false)->count();
        $blockedCommentsCount  = Review::where('is_comment_blocked', true)->count();
        $averageRating         = Review::avg('rating') ? round(Review::avg('rating'), 1) : 0.0;

        $reviews = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('reviews.index', compact(
            'reviews',
            'totalReviews',
            'activeCommentsCount',
            'blockedCommentsCount',
            'averageRating'
        ));
    }

    public function blockCommentReview(Request $request, Review $review)
    {
        $reason = $request->input('reason', 'Inappropriate language / bad words');

        $review->update([
            'is_comment_blocked' => true,
            'block_reason'       => $reason,
            'comment_blocked_at' => now(),
        ]);

        AuditLog::log('review_comment_blocked', $review, ['is_comment_blocked' => false], [
            'is_comment_blocked' => true,
            'reason'             => $reason,
        ]);

        $refNo = $review->booking->reference_no ?? 'Stay';
        \App\Models\NotificationModel::notifyUser(
            $review->user_id,
            'review',
            'Review Comment Hidden',
            "Your comment for stay {$refNo} was hidden by management for content policy violation ({$reason}). Note: Your {$review->rating}-star rating remains preserved and recorded.",
            ['review_id' => $review->id]
        );

        return back()->with('success', "Comment has been blocked for bad words. Note: Guest's {$review->rating}-star rating remains permanently preserved.");
    }

    public function unblockCommentReview(Review $review)
    {
        $review->update([
            'is_comment_blocked' => false,
            'block_reason'       => null,
            'comment_blocked_at' => null,
        ]);

        AuditLog::log('review_comment_unblocked', $review, ['is_comment_blocked' => true], [
            'is_comment_blocked' => false,
        ]);

        $refNo = $review->booking->reference_no ?? 'Stay';
        \App\Models\NotificationModel::notifyUser(
            $review->user_id,
            'review',
            'Review Comment Restored',
            "Your comment for stay {$refNo} has been restored and is now publicly visible.",
            ['review_id' => $review->id]
        );

        return back()->with('success', 'Comment has been unblocked and restored.');
    }

    public function approveReview(Review $review)
    {
        // Reviews are now auto-approved; keeping method for backward compatibility
        $review->update(['is_approved' => true]);
        AuditLog::log('review_approved', $review);

        return back()->with('success', 'Review is active.');
    }

    public function rejectReview(Review $review)
    {
        // If rejected, block the comment while strictly preserving the star rating
        $review->update([
            'is_comment_blocked' => true,
            'block_reason'       => 'Comment hidden by moderator (bad words / policy violation)',
            'comment_blocked_at' => now(),
        ]);
        AuditLog::log('review_comment_blocked', $review);
        return back()->with('success', "Comment has been blocked. Star rating of {$review->rating} stars remains preserved.");
    }

    // ─── Report Logic ─────────────────────────────────────────
    public function reportsIndex()
    {
        $driver = DB::getDriverName();
        $select = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
            : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total";

        $monthlyRevenue = Payment::where('status', 'success')
            ->selectRaw($select)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Expand to 90 days and include future dates so seeded capacity data shows
        $dailyOccupancy = collect(
            CapacitySchedule::whereBetween('date', [now()->subDays(89), now()->addDays(30)])
                ->orderBy('date')
                ->get()
                ->map(fn($c) => [
                    'date'        => $c->date->format('M d'),
                    'utilization' => $c->getUtilizationPercent(),
                ])
                ->values()
                ->toArray()
        );

        $popularAccommodations = AccommodationUnit::withCount(['bookings' => fn($q) =>
            $q->whereIn('status', ['paid', 'checked_in', 'completed'])
        ])->orderBy('bookings_count', 'desc')->take(7)->get();

        $ratingDist = Review::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $totalRevenue   = Payment::where('status', 'success')->sum('amount');
        $pendingAmount  = Payment::where('status', 'pending')->sum('amount');
        $refundedAmount = Payment::where('status', 'refunded')->sum('amount');
        $totalBookings  = Booking::count();
        $avgRating      = round(Review::where('is_approved', true)->avg('rating') ?? 0, 1);

        return view('reports.index', compact(
            'monthlyRevenue', 'dailyOccupancy', 'popularAccommodations',
            'ratingDist', 'totalRevenue', 'pendingAmount',
            'refundedAmount', 'totalBookings', 'avgRating'
        ));
    }

    public function exportPdf(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo   = $request->date_to ?? now()->format('Y-m-d');

        $paymentQuery   = Payment::successful();
        $totalRevenue   = $paymentQuery->clone()->sum('amount');
        $driver         = DB::getDriverName();
        $select         = $driver === 'sqlite'
            ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
            : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total";

        $monthlyRevenue = $paymentQuery->clone()
            ->selectRaw($select)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $pendingAmount  = Payment::where('status', 'pending')->sum('amount');
        $refundedAmount = Payment::where('status', 'refunded')->sum('amount');

        $dailyOccupancy = CapacitySchedule::whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get()
            ->map(fn($c) => [
                'date'          => $c->date->format('Y-m-d'),
                'current_count' => $c->current_count,
                'max_capacity'  => $c->max_capacity,
                'utilization'   => $c->getUtilizationPercent(),
            ]);

        $averageOccupancy = round($dailyOccupancy->avg('utilization'), 1);
        $popularAccommodations = AccommodationUnit::withCount(['bookings' => function ($q) {
            $q->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])->orderBy('bookings_count', 'desc')->take(10)->get();

        $reviewsQuery  = Review::approved();
        $averageRating = round($reviewsQuery->avg('rating') ?? 0, 1);
        $totalReviews  = $reviewsQuery->count();

        $pdf = Pdf::loadView('pdf.report', compact(
            'totalRevenue', 'pendingAmount', 'refundedAmount', 'monthlyRevenue',
            'dailyOccupancy', 'averageOccupancy', 'popularAccommodations', 'averageRating', 'totalReviews'
        ));

        return $pdf->download('talisay-resort-analytics-report.pdf');
    }

    public function exportExcel(Request $request)
    {
        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo   = $request->date_to ?? now()->format('Y-m-d');

        $totalRevenue   = Payment::successful()->sum('amount');
        $pendingAmount  = Payment::where('status', 'pending')->sum('amount');
        $refundedAmount = Payment::where('status', 'refunded')->sum('amount');

        $dailyOccupancy = CapacitySchedule::whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')->get();

        $popularAccommodations = AccommodationUnit::withCount(['bookings' => function ($q) {
            $q->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])->orderBy('bookings_count', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="talisay-analytics-report.csv"',
        ];

        return response()->stream(function () use ($totalRevenue, $pendingAmount, $refundedAmount, $dailyOccupancy, $popularAccommodations) {
            $file = fopen('php://output', 'w');

            fputcsv($file, ['Talisay Beach Resort Analytics Report']);
            fputcsv($file, ['Generated On', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            fputcsv($file, ['Financial Performance Indicators']);
            fputcsv($file, ['Indicator', 'Value (PHP)']);
            fputcsv($file, ['Gross Revenue', $totalRevenue]);
            fputcsv($file, ['Pending Balances', $pendingAmount]);
            fputcsv($file, ['Refunded Losses', $refundedAmount]);
            fputcsv($file, []);

            fputcsv($file, ['Resort Occupancy Logs']);
            fputcsv($file, ['Date', 'Booked Count', 'Cap Slots Limit', 'Utilization Rate (%)']);
            foreach ($dailyOccupancy as $day) {
                fputcsv($file, [
                    $day->date->format('Y-m-d'),
                    $day->current_count,
                    $day->max_capacity,
                    $day->getUtilizationPercent()
                ]);
            }
            fputcsv($file, []);

            fputcsv($file, ['Popular Rooms and Cottages']);
            fputcsv($file, ['Unit Number', 'Type', 'Nightly Rate', 'Max Occupancy', 'Confirmed Bookings']);
            foreach ($popularAccommodations as $unit) {
                fputcsv($file, [
                    $unit->unit_number,
                    $unit->type_label . ' (' . $unit->variant_label . ')',
                    $unit->price_per_night,
                    $unit->max_occupancy,
                    $unit->bookings_count
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    // ─── User Logic ───────────────────────────────────────────
    public function usersIndex(Request $request)
    {
        // The user list lives inside System Settings > User Accounts tab
        return redirect()->route('settings.index', array_merge(['tab' => 'users'], $request->only(['search', 'role', 'status', 'page'])));
    }

    public function userCreate()
    {
        return redirect()->route('settings.index', ['tab' => 'users', 'action' => 'create']);
    }

    public function userShow(User $user)
    {
        return redirect()->route('settings.index', ['tab' => 'users']);
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,staff,tourist',
        ]);

        if ($request->role === 'admin' && User::where('role', 'admin')->exists()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => 'Only one primary administrator account is allowed.',
            ]);
        }

        $newUser = User::create([
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
            'role'      => $request->role,
            'is_active' => true,
        ]);

        AuditLog::log('user_created', $newUser, null, ['role' => $newUser->role, 'created_by' => auth()->id()]);

        return redirect()->route('users.index')
            ->with('success', "Account for \"{$newUser->name}\" created successfully.");
    }

    public function userEdit(User $user)
    {
        return redirect()->route('settings.index', ['tab' => 'users']);
    }

    public function userUpdate(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'role'      => 'required|in:admin,staff,tourist',
            'is_active' => 'nullable|boolean',
            'password'  => 'nullable|string|min:8|confirmed',
        ]);

        if ($user->id === auth()->id() && !$request->boolean('is_active', false)) {
            return back()->withInput()->withErrors([
                'is_active' => 'You cannot deactivate your own administrative account.',
            ]);
        }

        $updateData = [
            'name'      => $request->name,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'role'      => $request->role,
            'is_active' => $request->boolean('is_active', false),
        ];

        if ($request->filled('password')) {
            $request->validate(['password' => 'string|min:8|confirmed']);
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        AuditLog::log('user_updated', $user, null, [
            'user_id'    => $user->id,
            'role'       => $user->role,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('users.index')
            ->with('success', "Account for \"{$user->name}\" updated successfully.");
    }

    public function userDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.index', ['tab' => 'users'])
                ->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        AuditLog::log('user_deleted', $user, null, ['user_id' => $user->id, 'deleted_by' => auth()->id()]);
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Account for \"{$userName}\" has been permanently deleted.");
    }

    public function userToggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('settings.index', ['tab' => 'users'])
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::log('user_status_updated', $user, null, [
            'user_id'   => $user->id,
            'is_active' => $user->is_active,
        ]);

        $statusStr = $user->is_active ? 'activated' : 'suspended';
        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Account for \"{$user->name}\" has been {$statusStr}.");
    }

    public function userResetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::log('user_password_reset', $user, null, ['user_id' => $user->id, 'reset_by' => auth()->id()]);

        return redirect()->route('settings.index', ['tab' => 'users'])
            ->with('success', "Password for \"{$user->name}\" has been reset successfully.");
    }

    // ─── Profile Logic ────────────────────────────────────────
    public function profileShow()
    {
        return view('profile.show');
    }

    public function profileUpdate(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone'  => 'nullable|string|max:20',
            'avatar' => 'nullable|image|max:2048',
        ]);

        $data = $request->only('name', 'email', 'phone');

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function profileDestroyAvatar(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }

        return back()->with('success', 'Profile picture removed successfully.');
    }

    public function updatePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'current_password' => 'required|current_password',
            'password'         => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => Hash::make($request->password),
        ]);

        AuditLog::log('password_changed', $user);

        return back()->with('success', 'Password updated successfully.');
    }

    // ─── Settings Logic ───────────────────────────────────────
    public function settingsIndex(?Request $request = null)
    {
        $request = $request ?? request();
        $settings = SystemSetting::all()->pluck('value', 'key');

        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->withCount(['bookings', 'reviews'])->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $totalUsers    = User::count();
        $adminCount    = User::where('role', 'admin')->count();
        $staffCount    = User::where('role', 'staff')->count();
        $touristCount  = User::where('role', 'tourist')->count();
        $activeCount   = User::where('is_active', true)->count();
        $inactiveCount = User::where('is_active', false)->count();

        $chatbotConfig = ChatbotConfig::current();
        /** @var ChatbotAIService $aiService */
        $aiService = app(ChatbotAIService::class);
        $aiProviders = $aiService->getProviders();

        return view('settings.index', compact(
            'settings',
            'users',
            'totalUsers',
            'adminCount',
            'staffCount',
            'touristCount',
            'activeCount',
            'inactiveCount',
            'chatbotConfig',
            'aiProviders'
        ));
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        foreach ($request->input('settings', []) as $key => $value) {
            if ($value !== null) {
                SystemSetting::set($key, $value);
            }
        }

        AuditLog::log('settings_updated', null, null, $request->input('settings'));

        return back()->with('success', 'Settings updated successfully.');
    }

    public function settingsUpdateChatbot(Request $request)
    {
        $request->validate([
            'provider'          => 'required|string|in:gemini,openai,anthropic,custom',
            'model'             => 'required|string|max:150',
            'api_key'           => 'nullable|string|max:500',
            'api_endpoint'      => 'nullable|string|max:500',
            'temperature'       => 'required|numeric|min:0|max:2',
            'max_tokens'        => 'required|integer|min:50|max:8000',
            'response_language' => 'required|string|in:en,fil,ceb,auto',
            'personality'       => 'required|string|in:friendly,professional,casual',
            'system_prompt'     => 'nullable|string|max:5000',
            'welcome_message'   => 'nullable|string|max:1000',
            'fallback_message'  => 'nullable|string|max:1000',
        ]);

        $config = ChatbotConfig::current();

        $data = [
            'provider'          => $request->provider,
            'model'             => trim($request->model),
            'api_endpoint'      => $request->api_endpoint ? trim($request->api_endpoint) : null,
            'temperature'       => (float) $request->temperature,
            'max_tokens'        => (int) $request->max_tokens,
            'is_enabled'        => $request->boolean('is_enabled', false),
            'response_language' => $request->response_language,
            'personality'       => $request->personality,
            'system_prompt'     => $request->system_prompt,
            'welcome_message'   => $request->welcome_message,
            'fallback_message'  => $request->fallback_message,
        ];

        // API Key logic:
        if ($request->boolean('remove_api_key')) {
            $data['api_key'] = null;
        } elseif ($request->filled('api_key')) {
            $newKey = trim($request->api_key);
            // Ignore placeholder bullet mask
            if (!str_contains($newKey, '••••')) {
                $data['api_key'] = $newKey;
            }
        }

        $config->update($data);

        // Keep SystemSetting in sync for quick fallback lookups
        if ($request->filled('welcome_message')) {
            SystemSetting::set('chatbot_welcome', $request->welcome_message);
        }
        if ($request->filled('fallback_message')) {
            SystemSetting::set('chatbot_fallback', $request->fallback_message);
        }
        if ($request->filled('system_prompt')) {
            SystemSetting::set('chatbot_persona', $request->system_prompt);
        }

        AuditLog::log('chatbot_settings_updated', null, null, [
            'provider'   => $config->provider,
            'model'      => $config->model,
            'is_enabled' => $config->is_enabled,
        ]);

        return redirect()->route('settings.index', ['tab' => 'chatbot'])->with('success', 'AI Chatbot settings updated successfully.');
    }

    public function settingsTestAiConnection(Request $request)
    {
        $request->validate([
            'provider'     => 'required|string|in:gemini,openai,anthropic,custom',
            'model'        => 'required|string|max:150',
            'api_key'      => 'nullable|string|max:500',
            'api_endpoint' => 'nullable|string|max:500',
        ]);

        /** @var ChatbotAIService $aiService */
        $aiService = app(ChatbotAIService::class);
        $result = $aiService->testConnection(
            $request->provider,
            $request->api_key,
            $request->model,
            $request->api_endpoint
        );

        return response()->json($result);
    }

}
