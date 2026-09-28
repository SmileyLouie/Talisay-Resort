<?php

namespace App\Http\Controllers;

use App\Models\AccommodationUnit;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\StaffPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends WebControllers
{
    public function index()
    {
        return $this->dashboardIndex();
    }

    /**
     * Dynamic Staff Dashboard based on assigned RBAC modules and permissions.
     */
    public function staffDashboard(Request $request)
    {
        $staff = Auth::user();

        // Redirect admin to admin dashboard
        if ($staff->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $assignedModules = $staff->assignedModules();

        $data = [
            'staff'           => $staff,
            'assignedModules' => $assignedModules,
            'moduleRegistry'  => StaffPermission::MODULES,
        ];

        // 1. Booking Data (if assigned)
        if ($staff->hasModuleAccess('bookings')) {
            $today = now()->format('Y-m-d');
            $data['todaysBookings'] = Booking::whereDate('booking_date', $today)
                ->with(['user', 'accommodationUnit'])
                ->orderBy('created_at', 'desc')
                ->take(8)
                ->get();

            $data['bookingStats'] = [
                'today_count'   => Booking::whereDate('booking_date', $today)->count(),
                'pending_count' => Booking::where('status', 'pending')->count(),
                'checked_in'    => Booking::where('status', 'checked_in')->count(),
                'total_guests'  => Booking::whereDate('booking_date', $today)->sum('guests_count'),
            ];

            $data['recentReservations'] = Booking::with(['user', 'accommodationUnit'])
                ->latest()
                ->take(6)
                ->get();
        }

        // 2. Payment Data (if assigned)
        if ($staff->hasModuleAccess('payments')) {
            $data['pendingPayments'] = Payment::where('status', 'pending')
                ->with('booking.user')
                ->latest()
                ->take(8)
                ->get();

            $data['paymentStats'] = [
                'pending_count'   => Payment::where('status', 'pending')->count(),
                'verified_today'  => Payment::where('status', 'success')->whereDate('updated_at', today())->count(),
                'today_collected' => Payment::where('status', 'success')->whereDate('updated_at', today())->sum('amount'),
            ];
        }

        // 3. Room & Cottage / Housekeeping Data (if assigned)
        if ($staff->hasModuleAccess('accommodations') || $staff->hasModuleAccess('housekeeping')) {
            $data['units'] = AccommodationUnit::with(['bookings' => fn($q) => $q->whereIn('status', ['paid', 'checked_in'])])
                ->orderBy('unit_number')
                ->get();

            $data['unitStats'] = [
                'total'     => AccommodationUnit::count(),
                'available' => AccommodationUnit::where('is_available', true)->count(),
                'occupied'  => AccommodationUnit::whereHas('bookings', fn($q) => $q->where('status', 'checked_in'))->count(),
            ];
        }


        // 5. Reviews Moderation Data (if assigned)
        if ($staff->hasModuleAccess('reviews')) {
            $data['pendingReviews'] = Review::with(['user', 'booking.accommodationUnit'])
                ->latest()
                ->take(5)
                ->get();
            $data['pendingReviewsCount'] = Review::count();
        }

        return view('staff.dashboard', $data);
    }
}
