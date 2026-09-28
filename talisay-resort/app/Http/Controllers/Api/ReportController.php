<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\AccommodationUnit;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function revenue(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Payment::successful();

        if ($request->date_from) {
            $query->where('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $totalRevenue = $query->clone()->sum('amount');
        
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        $select = $driver === 'sqlite' 
            ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
            : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total";

        $monthlyRevenue = $query->clone()
            ->selectRaw($select)
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        $refundedAmount = Payment::where('status', 'refunded')->sum('amount');

        return response()->json([
            'total_revenue' => $totalRevenue,
            'pending_amount' => $pendingAmount,
            'refunded_amount' => $refundedAmount,
            'monthly_breakdown' => $monthlyRevenue,
        ]);
    }

    public function occupancy(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $dateFrom = $request->date_from ?? now()->subDays(30)->format('Y-m-d');
        $dateTo = $request->date_to ?? now()->format('Y-m-d');

        $dailyOccupancy = \App\Models\CapacitySchedule::whereBetween('date', [$dateFrom, $dateTo])
            ->orderBy('date')
            ->get()
            ->map(fn($c) => [
                'date' => $c->date->format('Y-m-d'),
                'current_count' => $c->current_count,
                'max_capacity' => $c->max_capacity,
                'utilization' => $c->getUtilizationPercent(),
            ]);

        $avgOccupancy = $dailyOccupancy->avg('utilization');

        return response()->json([
            'average_occupancy' => round($avgOccupancy, 1),
            'daily_breakdown' => $dailyOccupancy,
        ]);
    }

    public function popularAccommodations(Request $request)
    {
        $accommodations = AccommodationUnit::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])
            ->orderBy('bookings_count', 'desc')
            ->take(10)
            ->get();

        return response()->json($accommodations);
    }

    public function cancellations(Request $request)
    {
        $totalBookings = Booking::count();
        $cancelledBookings = Booking::where('status', 'cancelled')->count();
        $cancellationRate = $totalBookings > 0 ? round(($cancelledBookings / $totalBookings) * 100, 2) : 0;

        $reasons = Booking::where('status', 'cancelled')
            ->whereNotNull('cancellation_reason')
            ->selectRaw('cancellation_reason, COUNT(*) as count')
            ->groupBy('cancellation_reason')
            ->pluck('count', 'cancellation_reason');

        return response()->json([
            'total_bookings' => $totalBookings,
            'cancelled_bookings' => $cancelledBookings,
            'cancellation_rate_percent' => $cancellationRate,
            'cancellation_reasons' => $reasons,
        ]);
    }

    public function satisfaction(Request $request)
    {
        $approvedReviews = Review::approved();
        $avgRating = round($approvedReviews->avg('rating') ?? 0, 1);
        $totalReviews = $approvedReviews->count();

        $ratingDistribution = Review::approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        return response()->json([
            'average_rating' => $avgRating,
            'total_reviews' => $totalReviews,
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}
