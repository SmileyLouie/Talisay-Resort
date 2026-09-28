<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BookingConflictException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Http\Controllers\Controller;
use App\Models\AccommodationUnit;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\NotificationModel;
use App\Services\BookingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function myBookings(Request $request)
    {
        $bookings = Booking::with(['accommodationUnit', 'payment', 'review'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json($bookings);
    }

    public function store(Request $request)
    {
        $request->validate([
            'accommodation_unit_id' => 'required|exists:accommodation_units,id',
            'booking_date'          => 'required|date|after_or_equal:today',
            'check_out_date'        => 'nullable|date|after:booking_date',
            'guests_count'          => 'required|integer|min:1|max:50',
            'special_requests'      => 'nullable|string|max:500',
            'payment_method'        => 'nullable|in:gcash,paypal,card,cash',
        ]);

        $user = $request->user();
        if (!$user->isTourist()) {
            return response()->json(['error' => 'Only guest accounts can create online reservations.'], 403);
        }

        $unit = AccommodationUnit::findOrFail($request->accommodation_unit_id);

        try {
            $booking = $this->bookings->createRegular([
                'unit'             => $unit,
                'check_in'         => $request->booking_date,
                'check_out'        => $request->check_out_date,
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $request->special_requests,
                'payment_method'   => $request->input('payment_method', 'gcash'),
                'booking_source'   => 'online',
            ]);
        } catch (BookingConflictException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        AuditLog::log('booking_created_by_tourist', $booking, null, $booking->toArray());

        NotificationModel::notifyUser(
            $user->id,
            'booking',
            'Reservation Submitted',
            "Your reservation for {$unit->unit_number} ({$booking->reference_no}) has been received. Please complete payment to secure your stay.",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );
        NotificationModel::notifyAdminsAndStaff(
            'booking',
            'New Reservation Received',
            "{$user->name} reserved {$unit->unit_number} from " . $booking->checkInDate()->format('M d') . ' to ' . $booking->checkOutDate()->format('M d, Y') . " (Ref: {$booking->reference_no}).",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        return response()->json([
            'booking' => $booking->load(['accommodationUnit', 'payment']),
            'message' => 'Booking created successfully. Please proceed to payment.',
        ], 201);
    }

    public function show(Request $request, Booking $booking)
    {
        Gate::authorize('view', $booking);

        $booking->load(['accommodationUnit', 'payment', 'review']);

        if ($request->user()->isAdmin() || $request->user()->isStaff()) {
            $booking->load('user');
        }

        return response()->json($booking);
    }

    public function update(Request $request, Booking $booking)
    {
        Gate::authorize('manage', $booking);

        $request->validate([
            'status'              => 'required|in:' . implode(',', Booking::STATUSES),
            'cancellation_reason' => 'required_if:status,cancelled|nullable|string|max:255',
        ]);

        try {
            $booking = $this->bookings->changeStatus($booking, $request->status, [
                'reason' => $request->cancellation_reason,
            ]);
        } catch (InvalidBookingTransitionException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (BookingConflictException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        $this->bookings->notifyGuestOfStatus($booking);

        return response()->json([
            'booking' => $booking->load(['user', 'accommodationUnit', 'payment']),
            'message' => 'Booking updated successfully.',
        ]);
    }

    public function cancel(Request $request, Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        $request->validate(['cancellation_reason' => 'nullable|string|max:255']);

        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_PAID], true)) {
            return response()->json(['error' => 'Only pending or paid reservations can be cancelled.'], 422);
        }

        $booking = $this->bookings->cancel($booking, $request->cancellation_reason, 'booking_cancelled_by_tourist');

        return response()->json(['message' => 'Booking cancelled successfully.', 'booking' => $booking]);
    }

    /**
     * Public month view of daily visitor capacity (aggregate only).
     */
    public function availability(Request $request)
    {
        $request->validate(['month' => 'sometimes|date_format:Y-m']);

        $startDate = Carbon::createFromFormat('Y-m', $request->month ?? now()->format('Y-m'))->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();
        $defaultCap = (int) setting('daily_visitor_cap', 100);

        $capacities = CapacitySchedule::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn ($c) => $c->date->format('Y-m-d'));

        $availability = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key      = $date->format('Y-m-d');
            $capacity = $capacities->get($key);
            $current  = $capacity ? (int) $capacity->current_count : 0;
            $max      = $capacity ? (int) $capacity->max_capacity : $defaultCap;
            $util     = $max > 0 ? ($current / $max) * 100 : 0;

            $availability[] = [
                'date'          => $key,
                'status'        => $util >= 100 ? 'full' : ($util >= 70 ? 'limited' : 'available'),
                'current_count' => $current,
                'max_capacity'  => $max,
                'utilization'   => round($util, 1),
            ];
        }

        return response()->json($availability);
    }
}
