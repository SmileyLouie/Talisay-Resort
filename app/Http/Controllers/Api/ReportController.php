<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\Review;
use App\Models\Package;
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

        // Calculate total successful revenue
        $totalRevenue = $query->clone()->sum('amount');
        
        // Detect database connection driver type (MySQL vs SQLite)
        $driver = \Illuminate\Support\Facades\DB::getDriverName();
        
        // Define database-specific query format for grouping by month
        $select = $driver === 'sqlite' 
            ? "strftime('%Y-%m', created_at) as month, SUM(amount) as total"
            : "DATE_FORMAT(created_at, '%Y-%m') as month, SUM(amount) as total";

        // Query monthly revenue grouped by month labels
        $monthlyRevenue = $query->clone()
            ->selectRaw($select)
            ->groupBy('month')
            ->orderBy('month')
            // Fetch the collection from database
            ->get();

        // Calculate total pending payment amounts
        $pendingAmount = Payment::where('status', 'pending')->sum('amount');
        // Calculate total refunded payment amounts
        $refundedAmount = Payment::where('status', 'refunded')->sum('amount');

        // Return statistical payload array as JSON response
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

    public function popularPackages(Request $request)
    {
        $packages = Package::withCount(['bookings' => function ($query) {
            $query->whereIn('status', ['paid', 'checked_in', 'completed']);
        }])
            ->orderBy('bookings_count', 'desc')
            ->take(10)
            ->get();

        return response()->json($packages);
    }

    public function cancellations(Request $request)
    {
        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
        ]);

        $query = Booking::where('status', 'cancelled');

        if ($request->date_from) {
            $query->where('cancelled_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->where('cancelled_at', '<=', $request->date_to);
        }

        $totalCancellations = $query->count();
        $totalBookings = Booking::count();
        $cancellationRate = $totalBookings > 0 ? round(($totalCancellations / $totalBookings) * 100, 1) : 0;

        $reasons = $query->clone()
            ->selectRaw('cancellation_reason, COUNT(*) as count')
            ->groupBy('cancellation_reason')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'total_cancellations' => $totalCancellations,
            'cancellation_rate' => $cancellationRate,
            'reasons' => $reasons,
        ]);
    }

    public function satisfaction()
    {
        $reviews = Review::approved();
        $totalReviews = $reviews->count();
        $averageRating = $reviews->avg('rating') ?? 0;

        $ratingDistribution = Review::approved()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->get()
            ->pluck('count', 'rating')
            ->toArray();

        return response()->json([
            'total_reviews' => $totalReviews,
            'average_rating' => round($averageRating, 1),
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}
