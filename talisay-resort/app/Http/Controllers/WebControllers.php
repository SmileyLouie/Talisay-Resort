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
use App\Exceptions\BookingConflictException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Models\AuditLog;
use App\Services\BookingService;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\ChatbotConfig;
use App\Models\ChatbotIntent;
use App\Models\ChatbotLog;
use App\Models\AccommodationUnit;
use App\Models\GalleryPhoto;
use App\Models\PaymentMethod;
use App\Support\PortalSites;
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
    /** @var array<string, \Illuminate\Support\Collection> */
    private array $dashboardStays = [];

    // ─── Dashboard Logic ──────────────────────────────────────
    public function dashboardIndex()
    {
        $today = now()->toDateString();
        $stayDate = 'date(coalesce(check_in_date, booking_date))';
        $stayStatuses = ['paid', 'checked_in', 'checked_out', 'completed'];

        $totalUnits = AccommodationUnit::count();

        $arrivalsToday = Booking::query()
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->whereRaw("$stayDate = ?", [$today])
            ->count();
        $pendingCount = Booking::query()->where('status', Booking::STATUS_PENDING)->count();

        $resortCapacity = \App\Models\CapacitySchedule::visitorCap();
        $todayStays = $this->staysOn($today);
        $resortFull = $todayStays->contains(fn ($booking) =>
            $booking->booking_type === 'special_resort' && $booking->admin_approval_status === 'approved'
        );
        $guestsToday = $resortFull ? $resortCapacity : (int) $todayStays->sum('guests_count');
        $capacityPercent = $resortCapacity > 0
            ? round(min(100, $guestsToday / $resortCapacity * 100), 1)
            : 0;

        $refundBase = Payment::query()->whereHas('booking');
        $refundsDue = (clone $refundBase)
            ->with('booking')
            ->whereIn('status', ['success', 'refunded'])
            ->whereHas('booking', fn ($booking) => $booking->where('status', Booking::STATUS_CANCELLED))
            ->get()
            ->filter(fn (Payment $payment) => $payment->refundState() === 'due');
        $refundDueCount = $refundsDue->count();
        $refundDueTotal = round($refundsDue->sum(fn (Payment $payment) => $payment->suggestedRefund()), 2);
        $refundsSent = (clone $refundBase)
            ->whereNotNull('metadata')
            ->get(['id', 'metadata'])
            ->filter(fn (Payment $payment) => $payment->refundSent());
        $refundSentCount = $refundsSent->count();
        $refundSentTotal = round($refundsSent->sum(fn (Payment $payment) => (float) $payment->refundSent()['amount']), 2);

        $monthStart = now()->startOfMonth();
        $monthlyRevenue = (float) Payment::where('status', 'success')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');
        $pendingPayments = (float) Payment::where('status', 'pending')->sum('amount');
        $pendingPaymentCount = Payment::where('status', 'pending')->count();

        $recentBookings = Booking::with(['user', 'accommodationUnit'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();

        $recentPayments = Payment::with(['booking.user', 'booking.accommodationUnit'])
            ->orderByDesc('created_at')
            ->take(8)
            ->get();
        $paymentMethodNames = PaymentMethod::labels();
        $paymentMethodIcons = PaymentMethod::ready()
            ? PaymentMethod::query()->pluck('icon', 'code')
            : collect();

        $occupancyData = collect(range(6, 0))->map(function (int $ago) use ($totalUnits) {
            $date = now()->subDays($ago)->toDateString();

            return [
                'date' => now()->subDays($ago)->format('M d'),
                'utilization' => $totalUnits > 0
                    ? round($this->unitsOccupiedOn($date, $totalUnits) / $totalUnits * 100, 1)
                    : 0,
            ];
        });

        $driver = DB::getDriverName();
        $select = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at) as month, SUM(amount) as total",
            'pgsql' => "to_char(created_at, 'YYYY-MM') as month, SUM(amount) as total",
            default => "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total",
        };

        $revenueByMonth = Payment::where('status', 'success')
            ->where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw($select)
            ->groupBy('month')
            ->pluck('total', 'month');

        $revenueTrends = collect(range(5, 0))->map(function (int $ago) use ($revenueByMonth) {
            $key = now()->subMonths($ago)->format('Y-m');

            return [
                'month' => now()->subMonths($ago)->format('M Y'),
                'total' => (float) ($revenueByMonth[$key] ?? 0),
            ];
        });

        $popularAccommodations = AccommodationUnit::withCount(['bookings' => fn ($q) =>
            $q->whereIn('status', $stayStatuses)
        ])->whereHas('bookings', fn ($q) => $q->whereIn('status', $stayStatuses))
            ->orderByDesc('bookings_count')
            ->take(6)
            ->get();

        $ratingDist = Review::query()
            ->where('is_approved', true)
            ->selectRaw('rating, COUNT(*) as total')
            ->groupBy('rating')
            ->pluck('total', 'rating');
        $sentimentData = [
            'positive' => (int) $ratingDist->filter(fn ($count, $rating) => (int) $rating >= 4)->sum(),
            'neutral'  => (int) ($ratingDist[3] ?? 0),
            'negative' => (int) $ratingDist->filter(fn ($count, $rating) => (int) $rating <= 2)->sum(),
        ];
        $reviewCount = array_sum($sentimentData);

        return view('dashboard.index', compact(
            'arrivalsToday', 'pendingCount',
            'guestsToday', 'resortCapacity', 'capacityPercent',
            'refundDueCount', 'refundDueTotal', 'refundSentCount', 'refundSentTotal',
            'monthlyRevenue', 'pendingPayments', 'pendingPaymentCount',
            'recentBookings', 'recentPayments', 'paymentMethodNames', 'paymentMethodIcons',
            'occupancyData', 'revenueTrends', 'popularAccommodations', 'sentimentData',
            'reviewCount'
        ));
    }

    private function occupyingQuery(string $date)
    {
        return Booking::query()
            ->where('status', '!=', Booking::STATUS_CANCELLED)
            ->where(function ($stay) use ($date) {
                $stay->occupyingDate($date)
                    ->orWhere(function ($hourly) use ($date) {
                        $hourly->where('stay_type', 'hour')
                            ->whereRaw('date(coalesce(check_in_date, booking_date)) = ?', [$date]);
                    });
            });
    }

    private function staysOn(string $date)
    {
        if (! array_key_exists($date, $this->dashboardStays)) {
            $this->dashboardStays[$date] = $this->occupyingQuery($date)->get([
                'booking_type',
                'admin_approval_status',
                'accommodation_unit_id',
                'guests_count',
            ]);
        }

        return $this->dashboardStays[$date];
    }

    private function unitsOccupiedOn(string $date, int $totalUnits): int
    {
        if ($totalUnits < 1) {
            return 0;
        }

        $stays = $this->staysOn($date);
        $exclusive = $stays->contains(fn ($booking) =>
            $booking->booking_type === 'special_resort' && $booking->admin_approval_status === 'approved'
        );

        if ($exclusive) {
            return $totalUnits;
        }

        return $stays->pluck('accommodation_unit_id')->filter()->unique()->count();
    }

    // ─── Authentication Logic ─────────────────────────────────

    /**
     * A guest who clicked Book is sent back to that page after they sign in.
     * Only same-site booking paths are accepted.
     */
    protected function safeBookingReturn(?string $target): ?string
    {
        $target = trim((string) $target);
        if ($target === '' || !str_starts_with($target, '/') || str_starts_with($target, '//') || str_contains($target, '\\')) {
            return null;
        }

        $parts = parse_url($target);
        if (!$parts || isset($parts['host']) || isset($parts['scheme'])) {
            return null;
        }

        $path = $parts['path'] ?? '';
        $allowed = $path === '/booking'
            || $path === '/booking/exclusive'
            || $path === '/rooms'
            || $path === '/cottages'
            || str_starts_with($path, '/stay/');

        return $allowed ? $target : null;
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|string|max:255',
            'password' => 'required|string',
            'portal'   => 'nullable|in:admin,client',
        ]);

        // Port 8000 is the client site. Port 8001 is the admin and staff site.
        // Other ports (automated tests) still honor the portal field.
        $site = PortalSites::current($request);
        if ($site === 'staff') {
            $request->merge(['portal' => 'admin']);
        } elseif ($site === 'client') {
            $request->merge(['portal' => 'client']);
        }

        // The admin portal is for administrators and staff. The client portal is
        // for guests only. A request with no portal (generic login) accepts any role.
        $portal     = in_array($request->input('portal'), ['admin', 'client'], true)
            ? $request->input('portal')
            : null;
        $loginInput = trim($request->input('email'));
        $reject = fn (string $message) => back()
            ->withErrors(['email' => $message])
            ->onlyInput('email', 'portal', 'redirect');

        // Staff may sign in with their Staff ID; everyone else with e-mail.
        $user = User::where(function ($q) use ($loginInput) {
            $q->where('email', $loginInput)->orWhere('staff_id', $loginInput);
        })->first();

        if (!$user) {
            return $reject('Wrong email or password. Please try again.');
        }

        if (!Hash::check($request->input('password'), $user->password)) {
            return $reject('Wrong email or password. Please try again.');
        }

        $allowedOnPortal = match ($portal) {
            'admin'  => $user->isAdmin() || $user->isStaff(),
            'client' => $user->isTourist(),
            default  => true,
        };

        if (!$allowedOnPortal) {
            $message = match (true) {
                $portal === 'client' && ($user->isAdmin() || $user->isStaff()) => 'This account signs in on the Admin & Staff site: '.PortalSites::staffBase().'/login',
                $portal === 'admin' && $user->isTourist() => 'This account signs in on the Client site: '.PortalSites::clientBase().'/login',
                default => 'This account cannot sign in on this page.',
            };

            return $reject($message);
        }

        if (!Auth::attempt(['email' => $user->email, 'password' => $request->input('password')], $request->boolean('remember'))) {
            return $reject('Wrong email or password. Please try again.');
        }

        if ($user->isStaff() && in_array($user->account_status, ['inactive', 'suspended'], true)) {
            Auth::logout();
            return back()
                ->withErrors(['email' => 'Your staff account is currently ' . $user->account_status . '. Please contact the resort administrator.'])
                ->onlyInput('email', 'portal', 'redirect');
        }

        if (!$user->is_active) {
            Auth::logout();
            return back()
                ->withErrors(['email' => 'Your account has been deactivated. Please contact support.'])
                ->onlyInput('email', 'portal', 'redirect');
        }

        $request->session()->regenerate();
        AuditLog::log('user_login', $user);

        if ($user->isAdmin()) return redirect()->intended(route('admin.dashboard'));
        if ($user->isStaff()) return redirect()->intended(route('staff.dashboard'));

        $next = $this->safeBookingReturn($request->input('redirect'));
        if ($next) {
            return redirect()->to($next);
        }

        return redirect()->intended(route('home'));
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

        // Redirect each role back to its own site. Tests do not use these ports.
        if (PortalSites::current($request) !== null) {
            if ($isAdminOrStaff) {
                return redirect()->away(PortalSites::staffUrl('/login'));
            }

            return redirect()->away(PortalSites::clientUrl('/'));
        }

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
            'email'    => ['required', 'string', 'email', 'max:255', User::uniqueActiveColumn('email')],
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

        $next = $this->safeBookingReturn($request->input('redirect'));

        return redirect()->to($next ?: route('home'))->with('success', 'Welcome to Talisay Beach Resort!');
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
            $stage = (string) $request->status;
            if (in_array($stage, ['pending', 'waiting', 'check_in', 'check_out', 'cancelled'], true)) {
                $query->listStage($stage);
            } else {
                $query->where('status', $stage);
            }
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

        if ($request->query('type') === 'exclusive') {
            $query->where('booking_type', 'special_resort');
        } elseif ($request->query('type') === 'hourly') {
            $query->where('stay_type', 'hour')->where('booking_type', '!=', 'special_resort');
        } elseif ($request->query('type') === 'nightly') {
            $query->where('booking_type', '!=', 'special_resort')
                ->where(function ($q) {
                    $q->whereNull('stay_type')->orWhere('stay_type', '!=', 'hour');
                });
        }

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(15)->withQueryString();
        $tourists = User::where('role', 'tourist')->where('is_active', true)->orderBy('name')->get(['id', 'name', 'email', 'phone']);
        $accommodationUnits = AccommodationUnit::where('is_available', true)->orderBy('unit_number')->get();
        $exclusiveNightlyRate = app(BookingService::class)->exclusiveNightlyRate();

        return view('bookings.index', compact('bookings', 'tourists', 'accommodationUnits', 'exclusiveNightlyRate'));
    }

    private function walkInGuestRules(): array
    {
        return [
            'first_name'  => 'exclude_unless:guest_type,walk_in|required|string|max:60',
            'middle_name' => 'exclude_unless:guest_type,walk_in|nullable|string|max:60',
            'last_name'   => 'exclude_unless:guest_type,walk_in|required|string|max:60',
            'guest_phone' => ['exclude_unless:guest_type,walk_in', 'nullable', 'required_without:guest_email', 'regex:/^[0-9+()\-\s]{7,20}$/'],
            'guest_email' => 'exclude_unless:guest_type,walk_in|nullable|required_without:guest_phone|email|max:120',
        ];
    }

    private function walkInGuestMessages(): array
    {
        return [
            'guest_phone.required_without' => 'Enter a contact number or an email address.',
            'guest_email.required_without' => 'Enter a contact number or an email address.',
            'guest_phone.regex'            => 'Enter a valid contact number.',
        ];
    }

    /** @return array{0: string, 1: string} full name and contact for a walk-in guest */
    private function walkInGuestDetails(Request $request): array
    {
        $join = fn (array $parts, string $glue) => collect($parts)
            ->map(fn ($part) => trim((string) $part))->filter()->implode($glue);

        return [
            $join([$request->first_name, $request->middle_name, $request->last_name], ' '),
            $join([$request->guest_phone, $request->guest_email], ' · '),
        ];
    }

    /**
     * Units that cannot be booked for the requested stay, keyed by unit id with a short reason.
     */
    public function manualUnitAvailability(Request $request)
    {
        $data = $request->validate([
            'date'       => 'required|date',
            'check_out'  => 'nullable|date',
            'stay_type'  => 'nullable|in:night,hour',
            'hour_start' => 'nullable|date_format:H:i',
            'hours'      => 'nullable|integer|min:1|max:12',
        ]);

        $hourly = ($data['stay_type'] ?? 'night') === 'hour' && !empty($data['hour_start']);
        $hours = $hourly ? (int) ($data['hours'] ?? 1) : null;

        if ($hourly) {
            $startAt = \Carbon\Carbon::parse(\Carbon\Carbon::parse($data['date'])->format('Y-m-d') . ' ' . $data['hour_start']);
            $endAt = $startAt->copy()->addHours($hours);
            $checkOutDay = $endAt->copy()->startOfDay();
            if ($endAt->gt($checkOutDay)) {
                $checkOutDay->addDay();
            }
            [$checkIn, $checkOut] = [$startAt->format('Y-m-d'), $checkOutDay->format('Y-m-d')];
        } else {
            [$checkIn, $checkOut] = Booking::normaliseStay($data['date'], $data['check_out'] ?? null);
        }

        $unavailable = [];
        foreach (AccommodationUnit::where('is_available', true)->get(['id']) as $unit) {
            $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut, null, $hourly ? $data['hour_start'] : null, $hours);
            if ($conflict) {
                $unavailable[$unit->id] = $conflict->booking_type === 'special_resort'
                    ? 'Exclusive booking'
                    : ($conflict->isHourly() && $conflict->time_slot
                        ? 'Booked ' . trim(preg_replace('/\s*\(.*\)$/', '', $conflict->time_slot))
                        : 'Booked');
            }
        }

        return response()->json(['unavailable' => (object) $unavailable]);
    }

    public function manualBookingStore(Request $request)
    {
        $request->validate([
            'guest_type'            => 'required|in:registered,walk_in',
            'user_id'               => 'required_if:guest_type,registered|nullable|exists:users,id',
            ...$this->walkInGuestRules(),
            'accommodation_unit_id'   => 'required_without:accommodation_unit_ids|nullable|exists:accommodation_units,id',
            'accommodation_unit_ids'  => 'required_without:accommodation_unit_id|array|min:1|max:20',
            'accommodation_unit_ids.*'=> 'distinct|exists:accommodation_units,id',
            'booking_date'          => 'required|date',
            'stay_type'             => 'nullable|in:night,hour',
            'check_out_date'        => 'nullable|date|after:booking_date',
            'hour_start'            => 'required_if:stay_type,hour|nullable|date_format:H:i',
            'hours_count'           => 'required_if:stay_type,hour|nullable|integer|min:1|max:12',
            'guests_count'          => 'required|integer|min:1|max:200',
            'payment_method'        => PaymentMethod::methodRule(),
            'initial_status'        => 'required|in:pending,paid,checked_in',
            'special_requests'      => 'nullable|string|max:500',
            'activity_ids'          => 'nullable|array|max:12',
            'activity_ids.*'        => 'integer|exists:resort_activities,id',
        ], $this->walkInGuestMessages() + [
            'accommodation_unit_ids.required_without' => 'Choose at least one room or cottage.',
            'accommodation_unit_ids.*.distinct'       => 'The same room or cottage was chosen twice.',
        ]);

        $unitIds = collect($request->input('accommodation_unit_ids', []))
            ->whenEmpty(fn ($ids) => $ids->push($request->accommodation_unit_id))
            ->map(fn ($id) => (int) $id)->unique()->values();
        $units = AccommodationUnit::whereIn('id', $unitIds)->get()->sortBy(fn ($u) => $unitIds->search($u->id))->values();

        $guestTotal = (int) $request->guests_count;
        $capacityTotal = (int) $units->sum('max_occupancy');
        if ($guestTotal < $units->count()) {
            return back()->with('error', 'Enter at least one guest for each room or cottage.')->withInput();
        }
        if ($guestTotal > $capacityTotal) {
            return back()->with('error', "The selected rooms and cottages hold up to {$capacityTotal} guests in total.")->withInput();
        }

        // One guest per unit first, then fill each unit up to its capacity.
        $remaining = $guestTotal - $units->count();
        $guestSplit = $units->map(function ($unit) use (&$remaining) {
            $extra = min($remaining, max(0, (int) $unit->max_occupancy - 1));
            $remaining -= $extra;
            return 1 + $extra;
        });

        $isRegistered = $request->guest_type === 'registered';
        $hourly = $request->input('stay_type') === 'hour';
        [$walkInName, $walkInContact] = $this->walkInGuestDetails($request);

        try {
            $bookings = DB::transaction(fn () => $units->map(fn ($unit, $i) => app(BookingService::class)->createRegular([
                'unit'                => $unit,
                'check_in'            => $request->booking_date,
                'check_out'           => $hourly ? null : $request->check_out_date,
                'stay_type'           => $hourly ? 'hour' : 'night',
                'hour_start'          => $hourly ? $request->hour_start : null,
                'hours_count'         => $hourly ? (int) $request->hours_count : null,
                'guests_count'        => $guestSplit[$i],
                'user_id'             => $isRegistered ? $request->user_id : null,
                'guest_name_manual'   => $isRegistered ? null : $walkInName,
                'guest_contact_manual'=> $isRegistered ? null : $walkInContact,
                'special_requests'    => $request->special_requests,
                'activity_ids'        => $i === 0 ? $request->input('activity_ids', []) : [],
                'status'              => $request->initial_status,
                'booking_source'      => 'manual',
                'payment_method'      => $request->payment_method,
                'created_by'          => Auth::id(),
            ])));
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        foreach ($bookings as $i => $booking) {
            AuditLog::log('manual_booking_created', $booking, null, [
                'created_by' => Auth::id(),
                'guest_type' => $request->guest_type,
                'booking'    => $booking->toArray(),
            ]);

            if ($booking->user_id) {
                \App\Models\NotificationModel::notifyUser(
                    $booking->user_id,
                    'booking',
                    'Reservation Created by Front Desk',
                    "A reservation for {$units[$i]->unit_number} ({$booking->reference_no}) was created for you by our resort staff.",
                    ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
                );
            }
        }

        $first = $bookings->first();
        $message = $bookings->count() === 1
            ? "Manual booking {$first->reference_no} created successfully for {$first->guest_name}."
            : "{$bookings->count()} manual bookings created for {$first->guest_name}: "
                . $bookings->map(fn ($b, $i) => "{$units[$i]->unit_number} ({$b->reference_no})")->implode(', ') . '.';

        return redirect()->route('bookings.index')->with('success', $message);
    }

    public function updateBookingStatus(Request $request, Booking $booking)
    {
        $request->validate([
            'status'              => 'required|in:' . implode(',', Booking::STATUSES),
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string|max:255',
        ]);

        try {
            app(BookingService::class)->changeStatus($booking, $request->status, [
                'reason' => $request->cancellation_reason,
            ]);
        } catch (InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        $booking = $booking->fresh();
        app(BookingService::class)->notifyGuestOfStatus($booking);

        $message = 'Booking status updated successfully.';
        if ($booking->status === Booking::STATUS_CANCELLED) {
            $message .= ' ' . app(BookingService::class)->describeCancellation($booking);
        }

        return back()->with('success', $message);
    }

    public function approveSpecialResortBooking(Request $request, Booking $booking)
    {
        try {
            $booking = app(BookingService::class)->approveSpecial($booking, Auth::id());
        } catch (InvalidBookingTransitionException|BookingConflictException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($booking->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'booking',
                'Special Resort Booking Approved',
                "Your exclusive full-resort booking ({$booking->reference_no}) has been approved. We look forward to hosting your event.",
                ['booking_id' => $booking->id]
            );
        }

        return back()->with('success', "Special full-resort booking {$booking->reference_no} has been approved. Resort inventory is locked for the selected dates.");
    }

    public function rejectSpecialResortBooking(Request $request, Booking $booking)
    {
        $reason = $request->input('reason', 'Special resort booking request was declined by management.');

        try {
            $booking = app(BookingService::class)->rejectSpecial($booking, Auth::id(), $reason);
        } catch (InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($booking->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'booking',
                'Special Resort Booking Update',
                "Your special full-resort booking ({$booking->reference_no}) could not be accommodated: {$reason}",
                ['booking_id' => $booking->id]
            );
        }

        return back()->with('success', "Special booking {$booking->reference_no} has been rejected.");
    }

    public function storeExclusiveBooking(Request $request)
    {
        $request->validate([
            'guest_type'     => 'required|in:registered,walk_in',
            'user_id'        => 'required_if:guest_type,registered|nullable|exists:users,id',
            ...$this->walkInGuestRules(),
            'check_in'       => 'required|date',
            'check_out'      => 'required|date|after:check_in',
            'guests_count'   => 'required|integer|min:1|max:'.\App\Models\CapacitySchedule::visitorCap(),
            'payment_method' => PaymentMethod::methodRule(),
            'initial_status' => 'required|in:pending,paid,checked_in',
            'occasion'       => ['required', 'string', 'max:120', function ($attribute, $value, $fail) {
                if (! in_array($value, Booking::occasions(), true)) {
                    $fail('Choose an occasion from the list.');
                }
            }],
            'activity_ids'   => 'nullable|array|max:12',
            'activity_ids.*' => 'integer|exists:resort_activities,id',
            'special_requests' => 'nullable|string|max:1000',
        ], $this->walkInGuestMessages());

        $isRegistered = $request->guest_type === 'registered';
        [$walkInName, $walkInContact] = $this->walkInGuestDetails($request);
        $details = trim((string) $request->special_requests);
        $notes = 'Occasion: ' . $request->occasion . ($details !== '' ? "\n\n" . $details : '');

        try {
            $booking = app(BookingService::class)->createSpecialResort([
                'check_in'            => $request->check_in,
                'check_out'           => $request->check_out,
                'guests_count'        => $request->guests_count,
                'user_id'             => $isRegistered ? $request->user_id : null,
                'guest_name_manual'   => $isRegistered ? null : $walkInName,
                'guest_contact_manual'=> $isRegistered ? null : $walkInContact,
                'special_requests'    => $notes,
                'activity_ids'        => $request->input('activity_ids', []),
                'payment_method'      => $request->payment_method,
                'booking_source'      => 'manual',
                'created_by'          => Auth::id(),
            ]);

            if ($request->initial_status !== 'pending') {
                $booking = app(BookingService::class)->approveSpecial($booking, Auth::id());
                if ($request->initial_status === 'checked_in') {
                    $booking = app(BookingService::class)->changeStatus($booking, Booking::STATUS_CHECKED_IN);
                }
            }
        } catch (BookingConflictException|InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        AuditLog::log('exclusive_booking_created', $booking, null, [
            'created_by' => Auth::id(),
            'booking'    => $booking->toArray(),
        ]);

        if ($booking->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'booking',
                'Exclusive Stay Created',
                "An exclusive full-resort stay ({$booking->reference_no}) was created for you by the resort.",
                ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
            );
        }

        return redirect()->route('bookings.index', ['type' => 'exclusive'])
            ->with('success', "Exclusive booking {$booking->reference_no} saved for {$booking->guest_name}.");
    }

    public function updateExclusiveBooking(Request $request, Booking $booking)
    {
        $request->validate([
            'check_in'          => 'required|date',
            'check_out'         => 'required|date|after:check_in',
            'guests_count'      => 'required|integer|min:1|max:'.\App\Models\CapacitySchedule::visitorCap(),
            'special_requests'  => 'nullable|string|max:1000',
            'guest_name'        => 'nullable|string|max:150',
            'guest_contact'     => 'nullable|string|max:100',
        ]);

        try {
            $booking = app(BookingService::class)->updateSpecialResort($booking, [
                'check_in'            => $request->check_in,
                'check_out'           => $request->check_out,
                'guests_count'        => $request->guests_count,
                'special_requests'    => $request->special_requests,
                'guest_name_manual'   => $request->guest_name,
                'guest_contact_manual'=> $request->guest_contact,
                'modification_notes'  => 'Exclusive stay updated by '.Auth::user()->name.'.',
            ]);
        } catch (BookingConflictException|InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        if ($booking->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'booking',
                'Exclusive Stay Updated',
                "Your exclusive stay {$booking->reference_no} was updated to ".$booking->checkInDate()->format('M d').' – '.$booking->checkOutDate()->format('M d, Y').'.',
                ['booking_id' => $booking->id]
            );
        }

        return redirect()->route('bookings.index', ['type' => 'exclusive'])
            ->with('success', "Exclusive booking {$booking->reference_no} was updated. New total is ₱".number_format((float) $booking->total_amount, 2).'.');
    }

    // ─── Payment Logic ────────────────────────────────────────
    public function paymentsIndex(Request $request)
    {
        // Payments whose booking was deleted are left out everywhere on this page.
        $base = fn () => Payment::query()->whereHas('booking');

        $query = $base()->with(['booking.user', 'booking.accommodationUnit']);
        $refundsDue = $base()->with('booking')
            ->whereIn('status', ['success', 'refunded'])
            ->whereHas('booking', fn ($bq) => $bq->where('status', Booking::STATUS_CANCELLED))
            ->get()
            ->filter(fn (Payment $payment) => $payment->refundState() === 'due');

        if ($request->status === 'refund_due') {
            $query->whereKey($refundsDue->modelKeys());
        } elseif ($request->filled('status')) {
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

        $totalSuccess = round((float) $base()->where('status', 'success')->sum('amount'), 2);
        $statusCounts = $base()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $gatewayCounts = $base()->selectRaw('gateway, count(*) as total')->groupBy('gateway')->pluck('total', 'gateway');
        $paymentMethods = PaymentMethod::catalog();

        $pending = $base()->where('status', 'pending')->get(['id', 'amount', 'proof_path', 'transaction_id']);
        $toReview = $pending->filter(fn (Payment $payment) => $payment->proof_path || $payment->guestReference());
        $totalPending = round((float) $toReview->sum('amount'), 2);
        $pendingCount = $toReview->count();
        $awaitingReceiptCount = $pending->count() - $pendingCount;

        $refundDueCount = $refundsDue->count();
        $refundDueTotal = round($refundsDue->sum(fn (Payment $payment) => $payment->suggestedRefund()), 2);

        $refundsSent = $base()->whereNotNull('metadata')->get(['id', 'metadata'])->filter(fn (Payment $payment) => $payment->refundSent());
        $refundSentCount = $refundsSent->count();
        $refundSentTotal = round($refundsSent->sum(fn (Payment $payment) => (float) $payment->refundSent()['amount']), 2);

        return view('payments.index', compact(
            'payments', 'totalSuccess', 'totalPending', 'pendingCount', 'awaitingReceiptCount', 'statusCounts', 'gatewayCounts', 'paymentMethods',
            'refundDueCount', 'refundDueTotal', 'refundSentCount', 'refundSentTotal'
        ));
    }

    public function paymentShow(Payment $payment)
    {
        $payment->load(['booking.user', 'booking.accommodationUnit']);
        return view('payments.show', compact('payment'));
    }

    public function approvePayment(Payment $payment)
    {
        try {
            $payment = app(BookingService::class)->approvePayment($payment);
        } catch (InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($payment->booking?->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $payment->booking->user_id,
                'payment',
                'Payment Confirmed',
                'Your payment of ₱' . number_format($payment->amount, 2) . ' for booking ' . $payment->booking->reference_no . ' has been confirmed.',
                ['booking_id' => $payment->booking_id]
            );
        }

        return back()->with('success', 'Payment approved successfully.');
    }

    public function rejectPayment(Payment $payment)
    {
        $payment = app(BookingService::class)->rejectPayment($payment);
        $booking = $payment->booking;
        $cancelled = $booking?->status === Booking::STATUS_CANCELLED;

        if ($booking?->user_id) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'payment',
                $cancelled ? 'Payment declined, booking cancelled' : 'Payment Proof Verification Issue',
                $cancelled
                    ? "Your payment for booking {$booking->reference_no} could not be verified, so the booking was cancelled. If you already sent money, the resort will contact you about a refund. You can book again anytime."
                    : "Your payment proof for booking {$booking->reference_no} could not be verified. Please re-upload a clear receipt or contact the front desk.",
                ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
            );
        }

        return back()->with('success', $cancelled
            ? "Payment declined. Booking {$booking->reference_no} was cancelled and its room or cottage is free again."
            : 'Payment declined.');
    }

    public function refundPayment(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'refund_amount' => 'required|numeric|min:0.01',
            'refund_reference' => 'nullable|string|max:100',
            'refund_note' => 'nullable|string|max:500',
        ], [
            'refund_amount.required' => 'Enter how much you refunded.',
            'refund_amount.min' => 'The refund must be more than ₱0.',
        ]);

        try {
            $payment = app(BookingService::class)->refundPayment(
                $payment,
                (float) $data['refund_amount'],
                $data['refund_reference'] ?? null,
                $data['refund_note'] ?? null,
                $request->user()
            );
        } catch (InvalidBookingTransitionException $e) {
            return back()->withErrors(['refund_amount' => $e->getMessage()])->withInput();
        }

        $sent = $payment->refundSent();

        return back()->with('success', 'Refund of ₱' . number_format($sent['amount'], 2) . ' recorded for ' . ($payment->booking->reference_no ?? 'this payment') . '.');
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
        $maxCap         = CapacitySchedule::visitorCap();
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
            'keyword'  => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', ChatbotIntent::CATEGORIES),
            'response' => 'required|string|max:2000',
        ]);

        $intent = ChatbotIntent::create([
            'keyword'   => $request->keyword,
            'category'  => $request->category,
            'response'  => $request->response,
            'is_active' => true,
        ]);

        AuditLog::log('chatbot_intent_created', $intent, null, $intent->toArray());

        return redirect()->route('chatbot.index')->with('success', 'Intent created successfully.');
    }

    public function chatbotUpdate(Request $request, ChatbotIntent $intent)
    {
        $request->validate([
            'keyword'   => 'required|string|max:255',
            'category'  => 'required|in:' . implode(',', ChatbotIntent::CATEGORIES),
            'response'  => 'required|string|max:2000',
            'is_active' => 'boolean',
        ]);

        $oldValues = $intent->toArray();
        $intent->update([
            'keyword'   => $request->keyword,
            'category'  => $request->category,
            'response'  => $request->response,
            'is_active' => $request->boolean('is_active', true),
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
        $assets = TourAsset::orderBy('sort_order')->orderBy('id')->paginate(20);
        $all = TourAsset::all();
        $tourStats = [
            'total'  => $all->count(),
            'shown'  => $all->where('is_active', true)->count(),
            'hidden' => $all->where('is_active', false)->count(),
            'photos' => $all->where('type', 'image')->count(),
            'flat'   => $all->where('type', 'image')->reject->isPanorama()->count(),
            'videos' => $all->where('type', 'video')->count(),
        ];

        return view('tour.index', compact('assets', 'tourStats'));
    }

    public function tourViewer()
    {
        $assets = TourAsset::active()->get();

        return view('tour.viewer', [
            'assets' => $assets,
            'navActive' => 'tour',
            'navSolid' => true,
            'resortInfo' => $this->clientResortInfo(),
        ]);
    }

    public function tourStore(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'nullable|string',
            'panorama'    => 'required|file|mimes:jpg,jpeg,png,webp,mp4,webm,ogv|max:51200',
            'type'        => 'required|in:image,video',
            'sort_order'  => 'required|integer',
        ]);

        $this->assertTourFileMatchesType($request->file('panorama'), $request->type);

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
            'panorama'    => 'nullable|file|mimes:jpg,jpeg,png,webp,mp4,webm,ogv|max:51200',
            'type'        => 'required|in:image,video',
            'sort_order'  => 'required|integer',
            'is_active'   => 'nullable',
        ]);

        $this->assertTourFileMatchesType($request->file('panorama'), $request->type);

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

    protected function assertTourFileMatchesType($file, string $type): void
    {
        if (!$file) {
            return;
        }

        $mime = (string) $file->getMimeType();
        $isImage = str_starts_with($mime, 'image/');
        $isVideo = str_starts_with($mime, 'video/');

        if ($type === 'image' && !$isImage) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'panorama' => 'Please upload an image file (JPG, PNG, or WebP) for image scenes.',
            ]);
        }

        if ($type === 'video' && !$isVideo) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'panorama' => 'Please upload a video file (MP4, WebM, or OGV) for video scenes.',
            ]);
        }
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

        if ($request->rating === 'low') {
            $query->where('rating', '<=', 2);
        } elseif ($request->filled('rating')) {
            $query->where('rating', (int) $request->rating);
        }

        if ($request->filled('search')) {
            $term = '%' . trim((string) $request->search) . '%';
            $query->where(function ($q) use ($term) {
                $q->where('comment', 'like', $term)
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term))
                  ->orWhereHas('booking', fn ($b) => $b->where('reference_no', 'like', $term)->orWhere('guest_name', 'like', $term));
            });
        }

        $totalReviews          = Review::count();
        $activeCommentsCount   = Review::where('is_comment_blocked', false)->count();
        $blockedCommentsCount  = Review::where('is_comment_blocked', true)->count();
        $averageRating         = Review::avg('rating') ? round(Review::avg('rating'), 1) : 0.0;
        $ratingCounts          = Review::selectRaw('rating, count(*) as total')->groupBy('rating')->pluck('total', 'rating');
        $lowRatingCount        = Review::where('rating', '<=', 2)->count();
        $thisMonthCount        = Review::where('created_at', '>=', now()->startOfMonth())->count();

        $reviews = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        return view('reviews.index', compact(
            'reviews',
            'totalReviews',
            'activeCommentsCount',
            'blockedCommentsCount',
            'averageRating',
            'ratingCounts',
            'lowRatingCount',
            'thisMonthCount'
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
        app(\App\Services\SupabaseSyncService::class)->pushReview($review);

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
        app(\App\Services\SupabaseSyncService::class)->pushReview($review);

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
    public function reportsIndex(Request $request)
    {
        $window = $this->reportDateWindow($request);
        extract($window, EXTR_SKIP);

        $confirmed = Payment::query()->where('status', 'success');
        $this->applyReportPaymentDates($confirmed, $from, $to, $dateType);

        $totalRevenue = (clone $confirmed)->sum('amount');
        $monthlyRevenue = $this->reportRevenueSeries(clone $confirmed, $group, $from, $to);

        $stayStatuses = ['paid', 'checked_in', 'checked_out', 'completed'];
        $popularFilter = function ($q) use ($stayStatuses, $from, $to, $dateType) {
            $q->whereIn('status', $stayStatuses);
            $this->applyReportBookingDates($q, $from, $to, $dateType === 'payment' ? 'booking' : $dateType);
        };
        $popularBase = fn (string $type) => AccommodationUnit::query()
            ->where('unit_type', $type)
            ->whereHas('bookings', $popularFilter)
            ->withCount(['bookings' => $popularFilter])
            ->orderByDesc('bookings_count')
            ->take(5)
            ->get()
            ->map(function ($unit) {
                $unit->chart_label = \Illuminate\Support\Str::of($unit->unit_number)
                    ->replace('_', ' ')
                    ->lower()
                    ->title()
                    ->toString();

                return $unit;
            });

        $popularRooms = $popularBase('room');
        $popularCottages = $popularBase('cottage');

        $ratingDist = Review::query()
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        $pendingQuery = Payment::query()->where('status', 'pending');
        $failedQuery = Payment::query()->where('status', 'failed');
        $this->applyReportPaymentDates($pendingQuery, $from, $to, $dateType);
        $this->applyReportPaymentDates($failedQuery, $from, $to, $dateType);

        $bookingQuery = Booking::query()->with(['user', 'accommodationUnit', 'payment']);
        $this->applyReportBookingDates($bookingQuery, $from, $to, $dateType === 'payment' ? 'booking' : $dateType);

        $pendingAmount = (clone $pendingQuery)->sum('amount');
        $failedAmount = (clone $failedQuery)->sum('amount');
        $refundedAmount = $this->reportRefundedTotal($from, $to, $dateType);
        $totalBookings = (clone $bookingQuery)->count();
        $avgRating = round(
            Review::query()
                ->where('is_approved', true)
                ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]))
                ->avg('rating') ?? 0,
            1
        );

        $salesPayments = (clone $confirmed)
            ->with(['booking.user', 'booking.accommodationUnit'])
            ->latest()
            ->limit(100)
            ->get();

        $paymentDetails = Payment::query()
            ->with(['booking.user', 'booking.accommodationUnit'])
            ->whereIn('status', ['pending', 'failed', 'refunded', 'success']);
        $this->applyReportPaymentDates($paymentDetails, $from, $to, $dateType);
        $paymentDetails = $paymentDetails->latest()->limit(120)->get();

        $reservationRows = (clone $bookingQuery)->latest()->limit(100)->get();

        $cancelledBookings = (clone $bookingQuery)->where('status', Booking::STATUS_CANCELLED)->get();
        $cancelledAmount = (float) $cancelledBookings->sum('total_amount');
        $cancelledCount = $cancelledBookings->count();
        $lossAmount = round((float) $failedAmount + (float) $refundedAmount, 2);

        $bookingStatusCounts = (clone $bookingQuery)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $paymentStatusCounts = Payment::query();
        $this->applyReportPaymentDates($paymentStatusCounts, $from, $to, $dateType);
        $paymentStatusCounts = $paymentStatusCounts
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('reports.index', compact(
            'monthlyRevenue', 'popularRooms', 'popularCottages',
            'ratingDist', 'totalRevenue', 'pendingAmount', 'failedAmount',
            'refundedAmount', 'totalBookings', 'avgRating',
            'period', 'dateType', 'dateFrom', 'dateTo',
            'revenueChoice', 'revenueRange', 'revenueChartTitle', 'dateTypeLabel',
            'salesPayments', 'paymentDetails', 'reservationRows',
            'cancelledAmount', 'cancelledCount', 'lossAmount',
            'bookingStatusCounts', 'paymentStatusCounts'
        ));
    }

    /**
     * Hotel/PMS-style report window: presets + custom from/to + date type.
     *
     * @return array{
     *   period:string,dateType:string,dateTypeLabel:string,dateFrom:?string,dateTo:?string,
     *   from:\Carbon\Carbon|null,to:\Carbon\Carbon|null,group:string,
     *   revenueChoice:string,revenueRange:string,revenueChartTitle:string
     * }
     */
    protected function reportDateWindow(Request $request): array
    {
        $allowed = ['today', 'yesterday', 'week', 'last_week', 'month', 'last_month', 'year', 'all', 'custom'];
        $period = (string) $request->input('period', 'month');
        if (! in_array($period, $allowed, true)) {
            $period = 'month';
        }

        $dateType = (string) $request->input('date_type', 'payment');
        if (! in_array($dateType, ['payment', 'booking', 'arrival'], true)) {
            $dateType = 'payment';
        }

        $now = now();
        $from = null;
        $to = null;
        $group = 'day';

        if ($period === 'custom' || ($request->filled('date_from') && $request->filled('date_to') && ! in_array($period, ['today', 'yesterday', 'week', 'last_week', 'month', 'last_month', 'year', 'all'], true))) {
            $period = 'custom';
            $from = \Carbon\Carbon::parse($request->input('date_from', $now->toDateString()))->startOfDay();
            $to = \Carbon\Carbon::parse($request->input('date_to', $now->toDateString()))->endOfDay();
            if ($from->gt($to)) {
                [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
            }
            $group = $from->diffInDays($to) > 62 ? 'month' : 'day';
        } else {
            [$from, $to, $group] = match ($period) {
                'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'day'],
                'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'day'],
                'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek(), 'day'],
                'last_week' => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->subWeek()->endOfWeek(), 'day'],
                'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth(), 'day'],
                'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), 'day'],
                'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear(), 'month'],
                default => [null, null, 'month'],
            };
        }

        $dateTypeLabel = match ($dateType) {
            'booking' => 'Booking date',
            'arrival' => 'Arrival (check-in) date',
            default => 'Payment date',
        };

        $revenueChoice = match ($period) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'week' => 'This week',
            'last_week' => 'Last week',
            'month' => 'This month',
            'last_month' => 'Last month',
            'year' => 'This year',
            'custom' => 'Custom range',
            default => 'All time',
        };

        $revenueRange = $from && $to
            ? ($from->isSameDay($to)
                ? $from->format('M j, Y')
                : $from->format('M j, Y').' – '.$to->format('M j, Y'))
            : 'All recorded activity';

        $revenueChartTitle = match ($period) {
            'today', 'yesterday' => 'Daily revenue',
            'week', 'last_week' => 'Weekly revenue',
            'month', 'last_month' => 'Monthly revenue',
            'year' => 'Yearly revenue',
            'custom' => 'Revenue for selected dates',
            default => 'Revenue over time',
        };

        return [
            'period' => $period,
            'dateType' => $dateType,
            'dateTypeLabel' => $dateTypeLabel,
            'dateFrom' => $from?->toDateString(),
            'dateTo' => $to?->toDateString(),
            'from' => $from,
            'to' => $to,
            'group' => $group,
            'revenueChoice' => $revenueChoice,
            'revenueRange' => $revenueRange,
            'revenueChartTitle' => $revenueChartTitle,
        ];
    }

    /** @deprecated Keep for older callers that still pass a period string. */
    protected function reportRevenueWindow(string $period): array
    {
        $request = request()->merge(['period' => $period]);
        $w = $this->reportDateWindow($request);

        return [$w['period'], $w['from'], $w['to'], $w['group'], $w['revenueChoice'], $w['revenueRange'], $w['revenueChartTitle']];
    }

    protected function applyReportPaymentDates($query, $from, $to, string $dateType): void
    {
        if (! $from || ! $to) {
            return;
        }

        if ($dateType === 'payment') {
            $table = $query->getModel()->getTable();
            $query->whereBetween($table.'.created_at', [$from, $to]);

            return;
        }

        $query->whereHas('booking', function ($q) use ($from, $to, $dateType) {
            $this->applyReportBookingDates($q, $from, $to, $dateType);
        });
    }

    protected function applyReportBookingDates($query, $from, $to, string $dateType): void
    {
        if (! $from || ! $to) {
            return;
        }

        $table = $query->getModel()->getTable();

        if ($dateType === 'arrival') {
            $query->whereDate($table.'.check_in_date', '>=', $from->toDateString())
                ->whereDate($table.'.check_in_date', '<=', $to->toDateString());

            return;
        }

        // booking date (and payment→booking fallback)
        $query->whereBetween($table.'.created_at', [$from, $to]);
    }

    protected function reportRefundedTotal($from, $to, string $dateType): float
    {
        $full = Payment::query()->where('status', 'refunded');
        $this->applyReportPaymentDates($full, $from, $to, $dateType);
        $fullTotal = (float) $full->sum('amount');

        $partialQuery = Payment::query()->where('status', 'success');
        $this->applyReportPaymentDates($partialQuery, $from, $to, $dateType);
        $partial = (float) $partialQuery->get()
            ->sum(fn (Payment $payment) => (float) ($payment->metadata['refund_amount'] ?? 0));

        return round($fullTotal + $partial, 2);
    }

    protected function reportRevenueSeries($payments, string $group, $from, $to)
    {
        $driver = DB::getDriverName();
        $select = match ($driver) {
            'sqlite' => $group === 'day'
                ? "strftime('%Y-%m-%d', created_at) as bucket, SUM(amount) as total"
                : "strftime('%Y-%m', created_at) as bucket, SUM(amount) as total",
            'pgsql' => $group === 'day'
                ? "to_char(created_at, 'YYYY-MM-DD') as bucket, SUM(amount) as total"
                : "to_char(created_at, 'YYYY-MM') as bucket, SUM(amount) as total",
            default => $group === 'day'
                ? "DATE_FORMAT(created_at, '%Y-%m-%d') as bucket, SUM(amount) as total"
                : "DATE_FORMAT(created_at, '%Y-%m') as bucket, SUM(amount) as total",
        };

        $rows = $payments->selectRaw($select)->groupBy('bucket')->orderBy('bucket')->pluck('total', 'bucket');
        $points = [];

        if ($group === 'day' && $from && $to) {
            for ($cursor = $from->copy()->startOfDay(); $cursor->lte($to); $cursor->addDay()) {
                $key = $cursor->format('Y-m-d');
                $points[] = (object) ['month' => $cursor->format('M j'), 'total' => (float) ($rows[$key] ?? 0)];
            }
        } elseif ($from && $to) {
            for ($cursor = $from->copy()->startOfMonth(); $cursor->lte($to); $cursor->addMonth()) {
                $key = $cursor->format('Y-m');
                $points[] = (object) ['month' => $cursor->format('M Y'), 'total' => (float) ($rows[$key] ?? 0)];
            }
        } else {
            foreach ($rows as $key => $total) {
                $label = strlen((string) $key) === 7
                    ? \Carbon\Carbon::createFromFormat('Y-m', $key)->format('M Y')
                    : (string) $key;
                $points[] = (object) ['month' => $label, 'total' => (float) $total];
            }
        }

        return collect($points);
    }

    public function exportPdf(Request $request)
    {
        $section = $this->reportExportSection($request);
        $bundle = $this->reportExportBundle($request);

        if ($section !== 'full') {
            $pdf = Pdf::loadView('pdf.report-section', array_merge($bundle, [
                'section' => $section,
                'sectionTitle' => $this->reportSectionTitle($section),
            ]));

            return $pdf->download('talisay-'.$section.'-report.pdf');
        }

        $pdf = Pdf::loadView('pdf.report', $bundle);

        return $pdf->download('talisay-resort-analytics-report.pdf');
    }

    protected function reportExportSection(Request $request): string
    {
        $section = (string) $request->input('section', 'full');

        return in_array($section, ['full', 'sales', 'bookings', 'payments'], true) ? $section : 'full';
    }

    protected function reportSectionTitle(string $section): string
    {
        return match ($section) {
            'sales' => 'Sales Report',
            'bookings' => 'Reservations Report',
            'payments' => 'Payments & Losses Report',
            default => 'Full Analytics Report',
        };
    }

    protected function reportExportBundle(Request $request): array
    {
        $window = $this->reportDateWindow($request);
        $from = $window['from'];
        $to = $window['to'];
        $group = $window['group'];
        $dateType = $window['dateType'];
        $period = $window['period'];
        $revenueRange = $window['revenueRange'];
        $dateFrom = $window['dateFrom'] ?? now()->subDays(30)->toDateString();
        $dateTo = $window['dateTo'] ?? now()->toDateString();

        $paymentQuery = Payment::successful();
        $this->applyReportPaymentDates($paymentQuery, $from, $to, $dateType);
        $totalRevenue = (clone $paymentQuery)->sum('amount');
        $monthlyRevenue = $this->reportRevenueSeries(clone $paymentQuery, $group, $from, $to);

        $pendingQuery = Payment::query()->where('status', 'pending');
        $failedQuery = Payment::query()->where('status', 'failed');
        $this->applyReportPaymentDates($pendingQuery, $from, $to, $dateType);
        $this->applyReportPaymentDates($failedQuery, $from, $to, $dateType);
        $pendingAmount = (clone $pendingQuery)->sum('amount');
        $failedAmount = (clone $failedQuery)->sum('amount');
        $refundedAmount = $this->reportRefundedTotal($from, $to, $dateType);

        $dailyOccupancy = CapacitySchedule::whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get()
            ->map(function ($c) {
                $max = CapacitySchedule::visitorCap();

                return [
                    'date' => $c->date->format('Y-m-d'),
                    'current_count' => $c->current_count,
                    'max_capacity' => $max,
                    'utilization' => $max > 0 ? round(min(100, $c->current_count / $max * 100), 1) : 0,
                ];
            });

        $averageOccupancy = round($dailyOccupancy->avg('utilization') ?? 0, 1);
        $stayStatuses = ['paid', 'checked_in', 'checked_out', 'completed'];
        $popularAccommodations = AccommodationUnit::withCount(['bookings' => function ($q) use ($stayStatuses, $from, $to, $dateType) {
            $q->whereIn('status', $stayStatuses);
            $this->applyReportBookingDates($q, $from, $to, $dateType === 'payment' ? 'booking' : $dateType);
        }])->orderByDesc('bookings_count')->take(10)->get();

        $reviewsQuery = Review::approved()
            ->when($from && $to, fn ($q) => $q->whereBetween('created_at', [$from, $to]));
        $averageRating = round($reviewsQuery->avg('rating') ?? 0, 1);
        $totalReviews = (clone $reviewsQuery)->count();

        $salesPayments = Payment::successful()
            ->with(['booking.user', 'booking.accommodationUnit']);
        $this->applyReportPaymentDates($salesPayments, $from, $to, $dateType);
        $salesPayments = $salesPayments->latest()->limit(200)->get();

        $paymentDetails = Payment::query()
            ->with(['booking.user', 'booking.accommodationUnit']);
        $this->applyReportPaymentDates($paymentDetails, $from, $to, $dateType);
        $paymentDetails = $paymentDetails->latest()->limit(200)->get();

        $reservationRows = Booking::query()
            ->with(['user', 'accommodationUnit', 'payment']);
        $this->applyReportBookingDates($reservationRows, $from, $to, $dateType === 'payment' ? 'booking' : $dateType);
        $reservationRows = $reservationRows->latest()->limit(200)->get();

        $cancelledAmount = (float) $reservationRows->where('status', Booking::STATUS_CANCELLED)->sum('total_amount');
        $cancelledCount = $reservationRows->where('status', Booking::STATUS_CANCELLED)->count();
        $lossAmount = round((float) $failedAmount + (float) $refundedAmount, 2);

        return array_merge($window, compact(
            'totalRevenue', 'pendingAmount', 'failedAmount', 'refundedAmount', 'monthlyRevenue',
            'dailyOccupancy', 'averageOccupancy', 'popularAccommodations', 'averageRating', 'totalReviews',
            'salesPayments', 'paymentDetails', 'reservationRows',
            'cancelledAmount', 'cancelledCount', 'lossAmount', 'revenueRange', 'period'
        ));
    }

    public function exportExcel(Request $request)
    {
        $section = $this->reportExportSection($request);
        $bundle = $this->reportExportBundle($request);
        $filename = $section === 'full'
            ? 'talisay-analytics-report.csv'
            : 'talisay-'.$section.'-report.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        return response()->stream(function () use ($section, $bundle) {
            $file = fopen('php://output', 'w');
            $title = $this->reportSectionTitle($section);

            fputcsv($file, ['Talisay Beach Resort — '.$title]);
            fputcsv($file, ['Period', $bundle['revenueRange'] ?? '']);
            fputcsv($file, ['Generated On', now()->format('Y-m-d H:i:s')]);
            fputcsv($file, []);

            if ($section === 'full' || $section === 'sales') {
                fputcsv($file, ['Sales Summary']);
                fputcsv($file, ['Confirmed revenue', $bundle['totalRevenue']]);
                fputcsv($file, []);
                fputcsv($file, ['Sales Report (confirmed payments)']);
                fputcsv($file, ['Date', 'Reference', 'Guest', 'Unit', 'Method', 'Amount (PHP)', 'Status']);
                foreach ($bundle['salesPayments'] as $payment) {
                    fputcsv($file, [
                        optional($payment->created_at)->format('Y-m-d H:i'),
                        $payment->booking?->reference_no ?? '—',
                        $payment->booking?->user?->name ?? 'Guest',
                        $payment->booking?->accommodationUnit?->unit_number ?? '—',
                        $payment->gateway ?: ($payment->payment_channel ?: '—'),
                        $payment->amount,
                        $payment->status,
                    ]);
                }
                fputcsv($file, []);
            }

            if ($section === 'full' || $section === 'bookings') {
                fputcsv($file, ['Reservations Report']);
                fputcsv($file, ['Date', 'Reference', 'Guest', 'Unit', 'Check-in', 'Check-out', 'Guests', 'Total (PHP)', 'Status']);
                foreach ($bundle['reservationRows'] as $booking) {
                    fputcsv($file, [
                        optional($booking->created_at)->format('Y-m-d H:i'),
                        $booking->reference_no,
                        $booking->user?->name ?? 'Guest',
                        $booking->accommodationUnit?->unit_number ?? ($booking->booking_type === 'special_resort' ? 'Exclusive resort' : '—'),
                        optional($booking->check_in_date)->format('Y-m-d'),
                        optional($booking->check_out_date)->format('Y-m-d'),
                        $booking->guests_count,
                        $booking->total_amount,
                        $booking->status,
                    ]);
                }
                fputcsv($file, []);
            }

            if ($section === 'full' || $section === 'payments') {
                fputcsv($file, ['Payments & Losses']);
                fputcsv($file, ['Pending', $bundle['pendingAmount']]);
                fputcsv($file, ['Failed', $bundle['failedAmount']]);
                fputcsv($file, ['Refunded', $bundle['refundedAmount']]);
                fputcsv($file, ['Cancelled booking value', $bundle['cancelledAmount']]);
                fputcsv($file, []);
                fputcsv($file, ['Payment Details']);
                fputcsv($file, ['Date', 'Reference', 'Guest', 'Method', 'Amount (PHP)', 'Status', 'Refund note']);
                foreach ($bundle['paymentDetails'] as $payment) {
                    $refund = $payment->refundSent();
                    fputcsv($file, [
                        optional($payment->created_at)->format('Y-m-d H:i'),
                        $payment->booking?->reference_no ?? '—',
                        $payment->booking?->user?->name ?? 'Guest',
                        $payment->gateway ?: ($payment->payment_channel ?: '—'),
                        $payment->amount,
                        $payment->status,
                        $refund['note'] ?? ($payment->metadata['refund_amount'] ?? ''),
                    ]);
                }
                fputcsv($file, []);
            }

            if ($section === 'full') {
                fputcsv($file, ['Resort Occupancy Logs']);
                fputcsv($file, ['Date', 'Booked Count', 'Cap Slots Limit', 'Utilization Rate (%)']);
                foreach ($bundle['dailyOccupancy'] as $day) {
                    fputcsv($file, [
                        $day['date'],
                        $day['current_count'],
                        $day['max_capacity'],
                        $day['utilization'],
                    ]);
                }
                fputcsv($file, []);

                fputcsv($file, ['Popular Rooms and Cottages']);
                fputcsv($file, ['Unit Number', 'Type', 'Nightly Rate', 'Max Occupancy', 'Confirmed Bookings']);
                foreach ($bundle['popularAccommodations'] as $unit) {
                    fputcsv($file, [
                        $unit->unit_number,
                        $unit->type_label.' ('.$unit->variant_label.')',
                        $unit->price_per_night,
                        $unit->max_occupancy,
                        $unit->bookings_count,
                    ]);
                }
            }

            fclose($file);
        }, 200, $headers);
    }

    // ─── User Logic ───────────────────────────────────────────
    public function usersIndex(Request $request)
    {
        // The user list lives inside System Settings > User Accounts tab
        return redirect()->route('maintenance.section', array_merge(['section' => 'accounts'], $request->only(['search', 'role', 'status', 'page'])));
    }

    public function userCreate()
    {
        return redirect()->route('maintenance.section', ['section' => 'accounts', 'action' => 'create']);
    }

    public function userShow(User $user)
    {
        return redirect()->route('maintenance.section', 'accounts');
    }

    public function userStore(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => ['required', 'string', 'email', 'max:255', User::uniqueActiveColumn('email')],
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
        return redirect()->route('maintenance.section', 'accounts');
    }

    public function userUpdate(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'string', 'email', 'max:255', User::uniqueActiveColumn('email', $user->id)],
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
            return redirect()->route('maintenance.section', 'accounts')
                ->with('error', 'You cannot delete your own account.');
        }

        $userName = $user->name;
        try {
            AuditLog::log('user_deleted', $user, null, ['user_id' => $user->id, 'deleted_by' => auth()->id()]);
            $user->eraseFromDatabase();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('users.index')
                ->with('error', "Account for \"{$userName}\" could not be removed. Try again.");
        }

        return redirect()->route('users.index')
            ->with('success', "Account for \"{$userName}\" was deleted from the database.");
    }

    public function userToggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->route('maintenance.section', 'accounts')
                ->with('error', 'You cannot suspend your own account.');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::log('user_status_updated', $user, null, [
            'user_id'   => $user->id,
            'is_active' => $user->is_active,
        ]);

        $statusStr = $user->is_active ? 'activated' : 'suspended';
        return redirect()->route('maintenance.section', 'accounts')
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

        return redirect()->route('maintenance.section', 'accounts')
            ->with('success', "Password for \"{$user->name}\" has been reset successfully.");
    }

    // ─── Profile Logic ────────────────────────────────────────
    public function profileShow()
    {
        $user = auth()->user();
        if ($user && $user->isTourist()) {
            return view('profile.client', [
                'resortInfo' => $this->clientResortInfo(),
            ]);
        }

        return view('profile.show');
    }

    /** Contact details the public client layout needs in the nav, footer, and chatbot. */
    protected function clientResortInfo(): array
    {
        $clock = function (?string $value, string $fallback): string {
            try {
                return \Carbon\Carbon::createFromFormat('H:i', (string) $value)->format('g:i A');
            } catch (\Throwable) {
                return $fallback;
            }
        };

        return [
            'name'        => setting('resort_name', 'Talisay Beach Resort'),
            'email'       => setting('contact_email', 'info@talisayresort.com'),
            'phone'       => setting('contact_phone', ''),
            'address'     => setting('resort_address', 'Barangay Maslug, Baybay City, Leyte'),
            'opens'       => $clock(setting('opening_time', '06:00'), '6:00 AM'),
            'closes'      => $clock(setting('closing_time', '22:00'), '10:00 PM'),
            'late_refund' => (int) setting('late_cancellation_refund_percent', 50),
            'same_day'    => (int) setting('same_day_cancellation_refund', 0),
            'gcash_name'  => setting('gcash_name', 'Talisay Beach Resort'),
        ];
    }

    public function profileUpdate(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => ['required', 'string', 'email', 'max:255', User::uniqueActiveColumn('email', $user->id)],
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
        return view('settings.index', $this->settingsViewData($request));
    }

    public function settingsViewData(?Request $request = null): array
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

        $galleryPhotos = GalleryPhoto::query()->orderBy('sort_order')->orderBy('id')->get();

        return compact(
            'settings',
            'users',
            'totalUsers',
            'adminCount',
            'staffCount',
            'touristCount',
            'activeCount',
            'inactiveCount',
            'chatbotConfig',
            'aiProviders',
            'galleryPhotos'
        );
    }

    public function settingsUpdate(Request $request)
    {
        $request->validate([
            'settings' => 'required|array',
        ]);

        $secretKeys = ['stripe_secret', 'stripe_webhook_secret'];
        $saved      = [];

        foreach ($request->input('settings', []) as $key => $value) {
            if ($value === null) {
                continue;
            }
            if (in_array($key, $secretKeys, true) && (trim((string) $value) === '' || str_contains((string) $value, '•'))) {
                continue;
            }
            if ($key === 'ui_locale' && ! in_array((string) $value, ['en', 'fil', 'ceb'], true)) {
                continue;
            }
            SystemSetting::set($key, $value);
            $saved[$key] = in_array($key, $secretKeys, true) ? '[redacted]' : $value;
        }

        if (array_key_exists('daily_visitor_cap', $saved)) {
            \App\Models\CapacitySchedule::query()->update([
                'max_capacity' => \App\Models\CapacitySchedule::visitorCap(),
            ]);
        }

        AuditLog::log('settings_updated', null, null, $saved);

        return back()->with('success', __('Settings updated successfully.'));
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

        \App\Models\SystemSetting::set('ai_hide_contacts', $request->boolean('hide_contacts') ? '1' : '0');

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

        return redirect()->route('maintenance.section', 'assistant')->with('success', 'AI Chatbot settings updated successfully.');
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
