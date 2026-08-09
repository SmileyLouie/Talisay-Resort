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
use App\Models\ChatbotIntent;
use App\Models\Emergency;
use App\Models\MemoryTimeline;
use App\Models\Package;
use App\Models\PackageSchedule;
use App\Models\Payment;
use App\Models\Review;
use App\Models\SystemSetting;
use App\Models\TourAsset;
use App\Models\User;
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
        $activeEmergencies = Emergency::unresolved()->count();
        $monthStart = now()->startOfMonth();
        $monthlyRevenue = Payment::where('status', 'success')
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');

        $recentBookings = Booking::with(['user', 'package'])
            ->orderBy('created_at', 'desc')
            ->take(10)->get();

        $occupancyData = CapacitySchedule::whereBetween('date', [now()->subDays(6), now()])
            ->orderBy('date')->get()
            ->map(fn($c) => [
                'date' => $c->date->format('M d'),
                'utilization' => $c->getUtilizationPercent(),
            ]);

        $upcomingReservations = Booking::with(['user', 'package'])
            ->where('booking_date', '>=', $today)
            ->whereIn('status', ['pending', 'paid'])
            ->orderBy('booking_date')
            ->take(5)->get();

        $sixMonthsAgo = now()->subMonths(5)->startOfMonth();
        $monthlyPayments = Payment::where('status', 'success')
            ->where('created_at', '>=', $sixMonthsAgo)
            ->get()
            ->groupBy(fn($p) => $p->created_at->format('Y-m'));

        $revenueTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i)->format('Y-m');
            $monthLabel = now()->subMonths($i)->format('M Y');
            $total = isset($monthlyPayments[$month]) ? $monthlyPayments[$month]->sum('amount') : 0;
            $revenueTrends[] = ['month' => $monthLabel, 'total' => $total];
        }

        $popularPackages = Booking::select('package_id', DB::raw('count(*) as count'))
            ->with('package')
            ->groupBy('package_id')
            ->orderByDesc('count')
            ->take(5)->get()
            ->map(fn($b) => [
                'name' => $b->package?->name ?? 'Deleted Package',
                'count' => $b->count,
            ]);

        $sentimentData = [
            'positive' => Review::where('rating', '>=', 4)->count(),
            'neutral'  => Review::where('rating', 3)->count(),
            'negative' => Review::where('rating', '<=', 2)->count(),
        ];

        return view('dashboard.index', compact(
            'todaysBookings', 'capacity', 'activeEmergencies', 'monthlyRevenue',
            'recentBookings', 'occupancyData', 'upcomingReservations',
            'revenueTrends', 'popularPackages', 'sentimentData'
        ));
    }

    // ─── Web Auth Logic ───────────────────────────────────────
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (!$user->is_active) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'email' => ['Your account has been deactivated.'],
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($user->isAdmin()) {
            return redirect()->intended(route('admin.dashboard'));
        } elseif ($user->isStaff()) {
            return redirect()->intended(route('staff.dashboard'));
        }

        return redirect()->intended('/');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'phone'    => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'avatar'   => 'nullable|image|max:2048',
        ]);

        $userData = [
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => 'tourist',
        ];

        if ($request->hasFile('avatar')) {
            $userData['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user = User::create($userData);
        Auth::login($user);

        return redirect('/')->with('success', 'Registration successful! Welcome to Talisay Beach Resort.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
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

    public function showResetPassword(Request $request)
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

    // ─── Package Logic ────────────────────────────────────────
    public function packagesIndex()
    {
        $packages = Package::with('schedules')->orderBy('created_at', 'desc')->paginate(12);
        return view('packages.index', compact('packages'));
    }

    public function packageStore(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string',
            'max_capacity' => 'required|integer|min:1',
            'is_visible'   => 'boolean',
        ]);

        $package = Package::create([
            'name'         => $request->name,
            'price'        => $request->price,
            'description'  => $request->description,
            'max_capacity' => $request->max_capacity,
            'is_visible'   => $request->boolean('is_visible', true),
            'images'       => ['packages/day-tour-1.jpg'],
        ]);

        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        foreach ($days as $day) {
            PackageSchedule::create([
                'package_id'      => $package->id,
                'day_of_week'     => $day,
                'start_time'      => '08:00:00',
                'end_time'        => '17:00:00',
                'available_slots' => $package->max_capacity,
            ]);
        }

        AuditLog::log('package_created', $package, null, $package->toArray());

        return redirect()->route('packages.index')->with('success', 'Package created successfully.');
    }

    public function packageUpdate(Request $request, Package $package)
    {
        $request->validate([
            'name'         => 'required|string|max:255',
            'price'        => 'required|numeric|min:0',
            'description'  => 'nullable|string',
            'max_capacity' => 'required|integer|min:1',
        ]);

        $oldValues = $package->toArray();

        $package->update([
            'name'         => $request->name,
            'price'        => $request->price,
            'description'  => $request->description,
            'max_capacity' => $request->max_capacity,
        ]);

        AuditLog::log('package_updated', $package, $oldValues, $package->toArray());

        return redirect()->route('packages.index')->with('success', 'Package updated successfully.');
    }

    public function packageToggleVisibility(Package $package)
    {
        $oldValues = $package->toArray();
        $package->update(['is_visible' => !$package->is_visible]);
        AuditLog::log('package_visibility_toggled', $package, $oldValues, $package->toArray());

        return redirect()->route('packages.index')->with('success', 'Package visibility status updated.');
    }

    public function packageDestroy(Package $package)
    {
        if ($package->bookings()->whereIn('status', ['pending', 'paid'])->exists()) {
            return redirect()->route('packages.index')->with('error', 'Cannot delete package. It has active bookings.');
        }

        $oldValues = $package->toArray();
        $package->schedules()->delete();
        $package->delete();

        AuditLog::log('package_deleted', null, $oldValues, null);

        return redirect()->route('packages.index')->with('success', 'Package deleted successfully.');
    }

    // ─── Booking Logic ────────────────────────────────────────
    public function bookingsIndex(Request $request)
    {
        $query = Booking::with(['user', 'package', 'payment']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->orderBy('booking_date', 'desc')->paginate(15);
        return view('bookings.index', compact('bookings'));
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

        return back()->with('success', 'Booking status updated successfully.');
    }

    // ─── Payment Logic ────────────────────────────────────────
    public function paymentsIndex(Request $request)
    {
        $query = Payment::with(['booking.user', 'booking.package']);

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
        $payment->load(['booking.user', 'booking.package']);
        return view('payments.show', compact('payment'));
    }

    public function approvePayment(Payment $payment)
    {
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Payment is not in pending status.');
        }

        $payment->update(['status' => 'success']);
        $payment->booking->update(['status' => 'paid']);

        AuditLog::log('payment_approved', $payment);

        return back()->with('success', 'Payment approved successfully.');
    }

    public function rejectPayment(Payment $payment)
    {
        $payment->update(['status' => 'failed']);
        AuditLog::log('payment_rejected', $payment);
        return back()->with('success', 'Payment rejected.');
    }

    // ─── Emergency Logic ──────────────────────────────────────
    public function emergenciesIndex(Request $request)
    {
        $query = Emergency::with(['user', 'responder']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $emergencies = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('emergencies.index', compact('emergencies'));
    }

    public function updateEmergencyStatus(Request $request, Emergency $emergency)
    {
        $request->validate([
            'status'         => 'required|in:pending,acknowledged,responding,resolved',
            'response_notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $emergency->status;

        $emergency->update([
            'status'         => $request->status,
            'responder_id'   => auth()->id(),
            'response_notes' => $request->response_notes,
            'resolved_at'    => $request->status === 'resolved' ? now() : $emergency->resolved_at,
        ]);

        AuditLog::log('emergency_status_updated', $emergency, ['status' => $oldStatus], ['status' => $emergency->status]);

        return back()->with('success', 'Emergency status updated to ' . ucfirst($request->status) . '.');
    }

    // ─── Chatbot Logic ────────────────────────────────────────
    public function chatbotIndex()
    {
        $intents    = ChatbotIntent::orderBy('category')->paginate(20);
        $categories = ChatbotIntent::distinct()->pluck('category');
        return view('chatbot.index', compact('intents', 'categories'));
    }

    public function chatbotStore(Request $request)
    {
        $request->validate([
            'keyword'   => 'required|string|max:255',
            'response'  => 'required|string',
            'category'  => 'required|string|max:100',
            'is_active' => 'boolean',
        ]);

        $intent = ChatbotIntent::create([
            'keyword'   => strtolower($request->keyword),
            'response'  => $request->response,
            'category'  => $request->category,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::log('chatbot_intent_created', $intent, null, $intent->toArray());

        return redirect()->route('chatbot.index')->with('success', 'Intent created successfully.');
    }

    public function chatbotUpdate(Request $request, ChatbotIntent $intent)
    {
        $request->validate([
            'keyword'  => 'required|string|max:255',
            'response' => 'required|string',
            'category' => 'required|string|max:100',
        ]);

        $oldValues = $intent->toArray();

        $intent->update([
            'keyword'  => strtolower($request->keyword),
            'response' => $request->response,
            'category' => $request->category,
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

    // ─── Memory Timeline Logic ────────────────────────────────
    public function memoryTimelineIndex()
    {
        $timelines = MemoryTimeline::with(['user', 'booking'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        return view('memory-timelines.index', compact('timelines'));
    }

    // ─── Review Logic ─────────────────────────────────────────
    public function reviewsIndex(Request $request)
    {
        $query = Review::with(['user', 'booking.package']);

        if ($request->filled('status')) {
            $query->where('is_approved', $request->status === 'approved');
        }

        $reviews = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('reviews.index', compact('reviews'));
    }

    public function approveReview(Review $review)
    {
        $review->update(['is_approved' => true]);
        AuditLog::log('review_approved', $review);
        return back()->with('success', 'Review approved.');
    }

    public function rejectReview(Review $review)
    {
        $review->update(['is_approved' => false]);
        AuditLog::log('review_rejected', $review);
        return back()->with('success', 'Review rejected.');
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

        $dailyOccupancy = CapacitySchedule::whereBetween('date', [now()->subDays(29), now()])
            ->orderBy('date')
            ->get()
            ->map(fn($c) => [
                'date'        => $c->date->format('M d'),
                'utilization' => $c->getUtilizationPercent(),
            ]);

        $popularPackages = Package::withCount(['bookings' => fn($q) =>
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
            'monthlyRevenue', 'dailyOccupancy', 'popularPackages',
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
        $popularPackages  = Package::withCount(['bookings' => function ($q) {
            $q->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])->orderBy('bookings_count', 'desc')->take(10)->get();

        $reviewsQuery  = Review::approved();
        $averageRating = round($reviewsQuery->avg('rating') ?? 0, 1);
        $totalReviews  = $reviewsQuery->count();

        $pdf = Pdf::loadView('pdf.report', compact(
            'totalRevenue', 'pendingAmount', 'refundedAmount', 'monthlyRevenue',
            'dailyOccupancy', 'averageOccupancy', 'popularPackages', 'averageRating', 'totalReviews'
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

        $popularPackages = Package::withCount(['bookings' => function ($q) {
            $q->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])->orderBy('bookings_count', 'desc')->get();

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="talisay-analytics-report.csv"',
        ];

        return response()->stream(function () use ($totalRevenue, $pendingAmount, $refundedAmount, $dailyOccupancy, $popularPackages) {
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

            fputcsv($file, ['Popular Resort Packages']);
            fputcsv($file, ['Package Title', 'Price Rate', 'Max Capacity Limit', 'Confirmed Bookings']);
            foreach ($popularPackages as $pkg) {
                fputcsv($file, [
                    $pkg->name,
                    $pkg->price,
                    $pkg->max_capacity,
                    $pkg->bookings_count
                ]);
            }

            fclose($file);
        }, 200, $headers);
    }

    // ─── User Logic ───────────────────────────────────────────
    public function usersIndex(Request $request)
    {
        $query = User::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('users.index', compact('users'));
    }

    public function userCreate()
    {
        return view('users.create');
    }

    public function userShow(User $user)
    {
        return redirect()->route('users.edit', $user);
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

        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'phone'    => $request->phone,
            'password' => Hash::make($request->password),
            'role'     => $request->role,
        ]);

        return redirect()->route('users.index')->with('success', 'User created successfully.');
    }

    public function userEdit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function userUpdate(Request $request, User $user)
    {
        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'phone'     => 'nullable|string|max:20',
            'role'      => 'required|in:admin,staff,tourist',
            'is_active' => 'boolean',
        ]);

        $user->update($request->only('name', 'email', 'phone', 'role', 'is_active'));

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function userDestroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User deleted.');
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
    public function settingsIndex()
    {
        $settings = SystemSetting::all()->pluck('value', 'key');
        return view('settings.index', compact('settings'));
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
}
