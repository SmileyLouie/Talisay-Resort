<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\Package;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with(['user', 'package', 'payment']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        if ($request->has('date_from')) {
            $query->where('booking_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('booking_date', '<=', $request->date_to);
        }
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $bookings = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json($bookings);
    }

    public function myBookings(Request $request)
    {
        $bookings = Booking::with(['package', 'payment', 'review'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'package_id' => 'required|exists:packages,id',
            'booking_date' => 'required|date|after_or_equal:today',
            'time_slot' => 'nullable|string',
            'guests_count' => 'required|integer|min:1',
            'special_requests' => 'nullable|string',
        ]);

        $package = Package::findOrFail($request->package_id);

        // Check slot lock to prevent double-booking
        $lockKey = "booking_lock_{$request->package_id}_{$request->booking_date}";
        if (Cache::has($lockKey)) {
            return response()->json(['error' => 'Someone is currently booking this slot. Please try again in a moment.'], 409);
        }

        // Lock slot for 5 minutes
        Cache::put($lockKey, true, 300);

        try {
            DB::beginTransaction();

            // Check capacity
            $capacity = CapacitySchedule::getCapacityForDate($request->booking_date);
            if (!$capacity->isAvailable($request->guests_count)) {
                Cache::forget($lockKey);
                return response()->json(['error' => 'Capacity exceeded for this date. Please choose another date.'], 422);
            }

            // Check for existing active booking by same user for same date
            $existingBooking = Booking::where('user_id', $request->user()->id)
                ->where('booking_date', $request->booking_date)
                ->whereIn('status', ['pending', 'paid', 'checked_in'])
                ->first();

            if ($existingBooking) {
                Cache::forget($lockKey);
                return response()->json(['error' => 'You already have an active booking for this date.'], 422);
            }

            $booking = Booking::create([
                'reference_no' => Booking::generateReferenceNo(),
                'user_id' => $request->user()->id,
                'package_id' => $request->package_id,
                'booking_date' => $request->booking_date,
                'time_slot' => $request->time_slot,
                'guests_count' => $request->guests_count,
                'special_requests' => $request->special_requests,
                'status' => 'pending',
                'total_amount' => $package->price * $request->guests_count,
            ]);

            // Update capacity
            $capacity->increment('current_count', $request->guests_count);

            DB::commit();

            // Release slot lock
            Cache::forget($lockKey);

            $booking->load(['package', 'user']);

            // Broadcast event
            broadcast(new \App\Events\BookingCreated($booking))->toOthers();

            return response()->json([
                'booking' => $booking,
                'message' => 'Booking created successfully! Please proceed to payment.',
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Cache::forget($lockKey);
            throw $e;
        }
    }

    public function show(Booking $booking)
    {
        $booking->load(['user', 'package', 'payment', 'memoryTimeline', 'review']);
        return response()->json($booking);
    }

    public function update(Request $request, Booking $booking)
    {
        $request->validate([
            'status' => 'sometimes|in:pending,paid,checked_in,checked_out,cancelled,completed',
            'cancellation_reason' => 'required_if:status,cancelled',
        ]);

        $oldStatus = $booking->status;

        if ($request->status === 'cancelled' && $oldStatus !== 'cancelled') {
            $booking->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $request->cancellation_reason,
            ]);

            // Release capacity
            $capacity = CapacitySchedule::getCapacityForDate($booking->booking_date);
            $capacity->decrement('current_count', $booking->guests_count);

            broadcast(new \App\Events\CapacityUpdated($capacity));
        } else {
            $booking->update($request->only('status'));
        }

        AuditLog::log('booking_status_changed', $booking, ['status' => $oldStatus], ['status' => $booking->status]);

        broadcast(new \App\Events\BookingUpdated($booking));

        return response()->json([
            'booking' => $booking->fresh()->load(['user', 'package', 'payment']),
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function availability(Request $request)
    {
        $request->validate([
            'package_id' => 'sometimes|exists:packages,id',
            'month' => 'sometimes|date_format:Y-m',
        ]);

        $month = $request->month ?? now()->format('Y-m');
        $startDate = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = $startDate->copy()->endOfMonth();

        $capacities = CapacitySchedule::whereBetween('date', [$startDate, $endDate])->get();

        $availability = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $capacity = $capacities->first(fn($c) => $c->date->format('Y-m-d') === $date->format('Y-m-d'));
            $currentCount = $capacity ? $capacity->current_count : 0;
            $maxCapacity = $capacity ? $capacity->max_capacity : setting('daily_visitor_cap', 100);
            $utilization = $maxCapacity > 0 ? ($currentCount / $maxCapacity) * 100 : 0;

            $status = 'available';
            if ($utilization >= 100) {
                $status = 'full';
            } elseif ($utilization >= 70) {
                $status = 'limited';
            }

            $availability[] = [
                'date' => $date->format('Y-m-d'),
                'status' => $status,
                'current_count' => $currentCount,
                'max_capacity' => $maxCapacity,
                'utilization' => round($utilization, 1),
            ];
        }

        return response()->json($availability);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $request->validate([
            'cancellation_reason' => 'required|string',
        ]);

        if (!in_array($booking->status, ['pending', 'paid'])) {
            return response()->json(['error' => 'This booking cannot be cancelled.'], 422);
        }

        $booking->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
            'cancellation_reason' => $request->cancellation_reason,
        ]);

        $capacity = CapacitySchedule::getCapacityForDate($booking->booking_date);
        $capacity->decrement('current_count', $booking->guests_count);

        broadcast(new \App\Events\CapacityUpdated($capacity));
        broadcast(new \App\Events\BookingUpdated($booking));

        return response()->json(['message' => 'Booking cancelled successfully.', 'booking' => $booking->fresh()]);
    }
}
