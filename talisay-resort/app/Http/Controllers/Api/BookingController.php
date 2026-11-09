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
use App\Models\PaymentMethod;
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
            'stay_type'             => 'nullable|in:night,hour',
            'hours_count'           => 'required_if:stay_type,hour|nullable|integer|min:1|max:12',
            'hour_start'            => ['required_if:stay_type,hour', 'nullable', 'regex:/^(?:[01]\d|2[0-3]):[0-5]\d$/'],
            'guests_count'          => 'required|integer|min:1|max:50',
            'special_requests'      => 'nullable|string|max:500',
            'activity_ids'          => 'nullable|array|max:12',
            'activity_ids.*'        => 'integer|exists:resort_activities,id',
            'payment_method'        => PaymentMethod::methodRule(false),
        ] + PaymentMethod::extraRules($request->input('payment_method')));

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
                'stay_type'        => $request->input('stay_type', 'night'),
                'hours_count'      => $request->input('hours_count'),
                'hour_start'       => $request->input('hour_start'),
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $request->special_requests,
                'activity_ids'     => $request->input('activity_ids', []),
                'payment_method'   => $request->input('payment_method', 'gcash'),
                'payment_plan'     => $request->input('payment_plan', 'full'),
                'gcash_method'     => $request->input('gcash_method', 'receipt'),
                'card_last_four'   => PaymentMethod::isCard($request->input('payment_method')) ? $request->input('card_last_four') : null,
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
            $booking->isHourly()
                ? "{$user->name} reserved {$unit->unit_number} on " . $booking->checkInDate()->format('M d, Y') . " {$booking->time_slot} (Ref: {$booking->reference_no})."
                : "{$user->name} reserved {$unit->unit_number} from " . $booking->checkInDate()->format('M d') . ' to ' . $booking->checkOutDate()->format('M d, Y') . " (Ref: {$booking->reference_no}).",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        return response()->json([
            'booking' => $booking->load(['accommodationUnit', 'payment']),
            'message' => 'Booking created successfully. Please proceed to payment.',
        ], 201);
    }

    public function storeSpecial(Request $request)
    {
        $request->validate([
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
            'guests_count'     => 'required|integer|min:1|max:'.\App\Models\CapacitySchedule::visitorCap(),
            'special_requests' => 'nullable|string|max:1000',
            'activity_ids'     => 'nullable|array|max:12',
            'activity_ids.*'   => 'integer|exists:resort_activities,id',
            'payment_method'   => PaymentMethod::methodRule(),
        ] + PaymentMethod::extraRules($request->input('payment_method')));

        $user = $request->user();
        if (!$user->isTourist()) {
            return response()->json(['error' => 'Only guest accounts can create online reservations.'], 403);
        }

        try {
            $booking = $this->bookings->createSpecialResort([
                'check_in'         => $request->check_in_date,
                'check_out'        => $request->check_out_date,
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $request->special_requests,
                'activity_ids'     => $request->input('activity_ids', []),
                'payment_method'   => $request->payment_method,
                'payment_plan'     => $request->input('payment_plan', 'full'),
                'gcash_method'     => $request->input('gcash_method', 'receipt'),
                'card_last_four'   => PaymentMethod::isCard($request->payment_method) ? $request->card_last_four : null,
            ]);
        } catch (BookingConflictException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        AuditLog::log('special_resort_booking_requested', $booking, null, $booking->toArray());

        NotificationModel::notifyUser(
            $user->id,
            'booking',
            'Exclusive Resort Booking Submitted',
            "Your exclusive full-resort booking request ({$booking->reference_no}) was submitted. Management will review and confirm availability.",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );
        NotificationModel::notifyAdminsAndStaff(
            'booking',
            'New Exclusive Resort Booking Request',
            "{$user->name} requested exclusive full-resort booking for {$booking->checkInDate()->format('M d')} to {$booking->checkOutDate()->format('M d, Y')} ({$booking->nights_count} nights).",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        return response()->json([
            'booking' => $booking->load(['accommodationUnit', 'payment']),
            'message' => 'Exclusive full-resort request submitted. Management will review it.',
        ], 201);
    }

    public function modify(Request $request, Booking $booking)
    {
        Gate::authorize('modify', $booking);

        $currentUnitId = $booking->accommodation_unit_id;
        $request->validate([
            'new_accommodation_unit_id' => [
                'required',
                'exists:accommodation_units,id',
                function ($attribute, $value, $fail) use ($currentUnitId) {
                    if ((int) $value === (int) $currentUnitId) {
                        $fail('Please select a different room or cottage.');
                    }
                },
            ],
            'modification_notes' => 'nullable|string|max:500',
        ]);

        $newUnit = AccommodationUnit::findOrFail($request->new_accommodation_unit_id);

        try {
            $result = $this->bookings->changeUnit($booking, $newUnit, $request->modification_notes);
            $booking = $result['booking'];
            $priceDiff = $result['price_diff'];
        } catch (BookingConflictException|InvalidBookingTransitionException $e) {
            return response()->json(['error' => $e->getMessage()], 409);
        }

        NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Modified',
            "Your booking {$booking->reference_no} has been switched to {$newUnit->unit_number}.",
            ['booking_id' => $booking->id]
        );
        NotificationModel::notifyAdminsAndStaff(
            'booking',
            'Booking Modified by Guest',
            $request->user()->name . " switched booking {$booking->reference_no} to {$newUnit->unit_number}.",
            ['booking_id' => $booking->id]
        );

        return response()->json([
            'booking' => $booking->load(['accommodationUnit', 'payment']),
            'price_diff' => $priceDiff,
            'message' => "Booking changed to {$newUnit->unit_number}.",
        ]);
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

    public function previewCancel(Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        return response()->json($this->bookings->previewCancellation($booking));
    }

    public function cancel(Request $request, Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        $request->validate(['cancellation_reason' => 'nullable|string|max:255']);

        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_PAID], true)) {
            return response()->json(['error' => 'Only pending or paid reservations can be cancelled.'], 422);
        }

        $booking = $this->bookings->cancel($booking, $request->cancellation_reason, 'booking_cancelled_by_tourist');
        $refundNote = $this->bookings->describeCancellation($booking);

        return response()->json(['message' => 'Booking cancelled. ' . $refundNote, 'booking' => $booking]);
    }

    /**
     * Public month view of daily visitor capacity (aggregate only).
     */
    public function availability(Request $request)
    {
        $request->validate(['month' => 'sometimes|date_format:Y-m']);

        $startDate = Carbon::createFromFormat('Y-m', $request->month ?? now()->format('Y-m'))->startOfMonth();
        $endDate   = $startDate->copy()->endOfMonth();

        $capacities = CapacitySchedule::whereBetween('date', [$startDate, $endDate])
            ->get()
            ->keyBy(fn ($c) => $c->date->format('Y-m-d'));

        $availability = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key      = $date->format('Y-m-d');
            $capacity = $capacities->get($key);
            $current  = $capacity ? (int) $capacity->current_count : 0;
            $max      = CapacitySchedule::visitorCap();
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
