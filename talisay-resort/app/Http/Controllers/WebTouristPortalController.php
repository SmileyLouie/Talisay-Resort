<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\AccommodationUnit;
use App\Models\NotificationModel;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Review;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class WebTouristPortalController extends WebControllers
{
    public function __construct(private BookingService $bookings) {}

    public function dashboard()
    {
        // The public homepage is the client home. This address only exists for old links.
        return redirect()->route('home');
    }

    public function bookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['accommodationUnit', 'payment', 'activities'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $accommodationUnits = AccommodationUnit::available()->orderBy('unit_number')->get();

        return view('tourist.bookings', [
            'bookings' => $bookings,
            'accommodationUnits' => $accommodationUnits,
            'resortInfo' => $this->clientResortInfo(),
        ]);
    }

    public function storeBooking(Request $request)
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
            'payment_method'        => PaymentMethod::methodRule(),
        ] + PaymentMethod::extraRules($request->input('payment_method')), PaymentMethod::proofMessages());

        if ($message = PaymentMethod::proofError($request)) {
            return back()->with('error', $message)->withInput();
        }

        $unit = AccommodationUnit::findOrFail($request->accommodation_unit_id);
        $user = Auth::user();

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
                'payment_method'   => $request->payment_method,
                'payment_plan'     => $request->input('payment_plan', 'full'),
                'gcash_method'     => $request->input('gcash_method', 'receipt'),
                'card_last_four'   => PaymentMethod::isCard($request->payment_method) ? $request->card_last_four : null,
                'booking_source'   => 'online',
            ]);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->attachOnlineProof($request, $booking);

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

        $booking->load('payment');
        $meta = $booking->payment->metadata ?? [];
        $due = number_format((float) $booking->payment->amount, 2);
        $balance = number_format((float) ($meta['balance'] ?? 0), 2);
        $planNote = ($meta['payment_plan'] ?? 'full') === 'down'
            ? " This is a 50% down payment of ₱{$due}. The remaining ₱{$balance} is due at the resort."
            : " The amount due now is ₱{$due}.";
        $hasProof = $request->hasFile('proof_image') || trim((string) $request->input('reference_number')) !== '';
        $msg = match (true) {
            PaymentMethod::needsProof($request->payment_method) => ($request->input('gcash_method') === 'qr'
                ? "Booking {$booking->reference_no} created for {$unit->unit_number}. The payment QR is fixed at ₱{$due}."
                : ($hasProof
                    ? "Booking {$booking->reference_no} created for {$unit->unit_number}. Your receipt was sent to the resort for approval."
                    : "Booking {$booking->reference_no} created for {$unit->unit_number}. Please upload your receipt to confirm.")) . $planNote,
            PaymentMethod::isCash($request->payment_method) => "Booking {$booking->reference_no} created for {$unit->unit_number}. Please pay on arrival and present your reference number at the front desk.",
            PaymentMethod::isCard($request->payment_method) => "Booking {$booking->reference_no} created for {$unit->unit_number}. Card details were recorded for ₱{$due}." . $planNote,
            default => "Booking {$booking->reference_no} created for {$unit->unit_number}. Please complete payment to confirm your reservation.",
        };

        if ($request->boolean('wizard')) {
            return redirect()->route('public.booking', ['done' => $booking->reference_no])->with('success', $msg);
        }

        return redirect()->route('tourist.bookings')->with('success', $msg);
    }

    public function modifyBooking(Request $request, Booking $booking)
    {
        Gate::authorize('modify', $booking);

        $currentUnitId = $booking->accommodation_unit_id;
        $request->validate([
            'new_accommodation_unit_id' => [
                'required',
                'exists:accommodation_units,id',
                function ($attribute, $value, $fail) use ($currentUnitId) {
                    if ((int) $value === (int) $currentUnitId) {
                        $fail('Please select a different room or cottage to modify your booking.');
                    }
                },
            ],
            'modification_notes' => 'nullable|string|max:500',
        ]);

        $newUnit = AccommodationUnit::findOrFail($request->new_accommodation_unit_id);

        try {
            $result    = $this->bookings->changeUnit($booking, $newUnit, $request->modification_notes);
            $booking   = $result['booking'];
            $priceDiff = $result['price_diff'];
        } catch (BookingConflictException|InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
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
            Auth::user()->name . " switched booking {$booking->reference_no} to {$newUnit->unit_number}.",
            ['booking_id' => $booking->id]
        );

        $diffMsg = $priceDiff > 0
            ? 'An additional ₱' . number_format($priceDiff, 2) . ' is due. Please re-upload payment proof.'
            : ($priceDiff < 0
                ? 'A refund of ₱' . number_format(abs($priceDiff), 2) . ' will be processed by the front desk.'
                : 'No price change.');

        return redirect()->route('tourist.bookings')
            ->with('success', "Booking {$booking->reference_no} changed to {$newUnit->unit_number}. {$diffMsg}");
    }

    public function cancelBooking(Request $request, Booking $booking)
    {
        Gate::authorize('cancel', $booking);

        if (!in_array($booking->status, [Booking::STATUS_PENDING, Booking::STATUS_PAID], true)) {
            return back()->with('error', 'Only pending or paid reservations can be cancelled.');
        }

        try {
            $booking = $this->bookings->cancel($booking, $request->input('cancellation_reason'), 'booking_cancelled_by_tourist');
        } catch (InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        $refundNote = $this->bookings->describeCancellation($booking);

        NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Cancelled',
            "Your reservation {$booking->reference_no} has been cancelled. {$refundNote}",
            ['booking_id' => $booking->id]
        );
        NotificationModel::notifyAdminsAndStaff(
            'booking',
            'Reservation Cancelled by Guest',
            Auth::user()->name . " cancelled reservation {$booking->reference_no}. {$refundNote}",
            ['booking_id' => $booking->id]
        );

        return redirect()->route('tourist.bookings')
            ->with('success', "Reservation {$booking->reference_no} has been cancelled. {$refundNote}");
    }

    public function storeSpecialResortBooking(Request $request)
    {
        $request->validate([
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
            'guests_count'     => 'required|integer|min:1|max:'.\App\Models\CapacitySchedule::visitorCap(),
            'special_requests' => 'nullable|string|max:1000',
            'occasion'         => ['nullable', 'string', 'max:120', function ($attribute, $value, $fail) {
                if ($value !== null && $value !== '' && ! in_array($value, Booking::occasions(), true)) {
                    $fail('Choose an occasion from the list.');
                }
            }],
            'activity_ids'     => 'nullable|array|max:12',
            'activity_ids.*'   => 'integer|exists:resort_activities,id',
            'payment_method'   => PaymentMethod::methodRule(),
        ] + PaymentMethod::extraRules($request->input('payment_method')), PaymentMethod::proofMessages());

        if ($message = PaymentMethod::proofError($request)) {
            return back()->with('error', $message)->withInput();
        }

        $user = Auth::user();

        try {
            $booking = $this->bookings->createSpecialResort([
                'check_in'         => $request->check_in_date,
                'check_out'        => $request->check_out_date,
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $this->occasionNote($request),
                'activity_ids'     => $request->input('activity_ids', []),
                'payment_method'   => $request->payment_method,
                'payment_plan'     => $request->input('payment_plan', 'full'),
                'gcash_method'     => $request->input('gcash_method', 'receipt'),
                'card_last_four'   => PaymentMethod::isCard($request->payment_method) ? $request->card_last_four : null,
            ]);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        $this->attachOnlineProof($request, $booking);

        AuditLog::log('special_resort_booking_requested', $booking, null, $booking->toArray());

        NotificationModel::notifyUser(
            $user->id,
            'booking',
            'Exclusive Resort Booking Submitted',
            "Your exclusive full-resort booking request ({$booking->reference_no}) was submitted. Management will review and confirm availability.",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );
        NotificationModel::notifyAdminsOnly(
            'booking',
            'New Exclusive Resort Booking Request',
            "{$user->name} requested exclusive full-resort booking for {$booking->checkInDate()->format('M d')} to {$booking->checkOutDate()->format('M d, Y')} ({$booking->nights_count} nights, ₱" . number_format($booking->total_amount, 2) . ').',
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        $msg = "Exclusive full-resort request {$booking->reference_no} was sent. Management will review it before the whole resort is reserved for your dates.";

        if ($request->boolean('wizard')) {
            return redirect()->route('public.booking.exclusive', ['done' => $booking->reference_no])->with('success', $msg);
        }

        return redirect()->route('tourist.bookings')->with('success', $msg);
    }

    public function checkAvailability(Request $request, AccommodationUnit $unit)
    {
        $hourly = $request->query('stay_type') === 'hour';
        $hours = max(1, min(12, (int) $request->query('hours', 1)));
        $hourStart = (string) $request->query('hour_start', '');
        if ($hourly && preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hourStart)) {
            $startAt = \Carbon\Carbon::parse($request->query('check_in', now()->format('Y-m-d')) . ' ' . $hourStart);
            $endAt = $startAt->copy()->addHours($hours);
            $exclusive = $endAt->copy()->startOfDay();
            if ($endAt->gt($exclusive)) {
                $exclusive->addDay();
            }
            $checkIn = $startAt->format('Y-m-d');
            $checkOut = $exclusive->format('Y-m-d');
            $nights = 0;
            $total = round($unit->hourlyRate() * $hours, 2);
            $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut, null, $hourStart, $hours);
        } else {
            [$checkIn, $checkOut, $nights] = Booking::normaliseStay(
                $request->query('check_in', now()->format('Y-m-d')),
                $request->query('check_out', now()->addDay()->format('Y-m-d'))
            );
            $total = (float) ($unit->price_per_night * $nights);
            $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut);
        }

        $isAvailable = ($conflict === null) && $unit->is_available;

        return response()->json([
            'available'        => $isAvailable,
            'unit_id'          => $unit->id,
            'unit_name'        => $unit->unit_number,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'nights'           => $nights,
            'hours'            => $hourly ? $hours : null,
            'price_per_night'  => (float) $unit->price_per_night,
            'price_per_hour'   => $unit->hourlyRate(),
            'total_amount'     => $total,
            'conflict_message' => $conflict ? ($hourly ? "{$unit->unit_number} is already booked for that time." : "{$unit->unit_number} is already booked on this date.") : null,
            'booked_ranges'    => Booking::getBookedDateRangesForUnit($unit->id),
        ]);
    }

    private function attachOnlineProof(Request $request, Booking $booking): void
    {
        if (! PaymentMethod::needsProof($request->payment_method)) {
            return;
        }

        $payment = $booking->payment()->first();
        if (!$payment) {
            return;
        }

        $reference = preg_replace('/\D+/', '', (string) $request->input('reference_number'));
        $updates = [];

        if ($request->hasFile('proof_image')) {
            $updates['proof_path'] = $request->file('proof_image')->store('payments', 'public');
        }

        if ($reference !== '') {
            $updates['transaction_id'] = $reference;
        }

        if ($updates !== []) {
            $payment->update($updates);
            NotificationModel::notifyAdminsAndStaff(
                'payment',
                'Payment proof submitted',
                Auth::user()->name . " sent payment proof for booking {$booking->reference_no}. Review it and approve the payment.",
                ['booking_id' => $booking->id, 'payment_id' => $payment->id]
            );
        }
    }

    public function uploadPaymentProof(Request $request, Payment $payment)
    {
        Gate::authorize('uploadProof', $payment);

        $request->validate([
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
            'reference_number' => 'required|string|max:40',
        ], [
            'proof_image.required' => 'Upload the GCash receipt screenshot.',
            'reference_number.required' => 'Enter the 13-digit GCash transaction reference number.',
        ]);

        $reference = preg_replace('/\D+/', '', (string) $request->input('reference_number'));
        if (!preg_match('/^\d{13}$/', $reference)) {
            return redirect()->route('tourist.bookings')->with('error', 'Enter the 13-digit GCash transaction reference number.');
        }

        if ($payment->status === 'success') {
            return redirect()->route('tourist.bookings')->with('error', 'This payment has already been verified.');
        }

        if ($payment->booking?->status === Booking::STATUS_CANCELLED) {
            return redirect()->route('tourist.bookings')->with('error', 'This booking was cancelled, so it no longer takes payments. Please make a new booking.');
        }

        $updates = [
            'payment_channel' => $payment->payment_channel ?? 'gcash',
            'status'          => 'pending',
        ];

        if ($request->hasFile('proof_image')) {
            if ($payment->proof_path) {
                Storage::disk('public')->delete($payment->proof_path);
            }
            $updates['proof_path'] = $request->file('proof_image')->store('payments', 'public');
        }

        if ($reference !== '') {
            $updates['transaction_id'] = $reference;
        }

        $payment->update($updates);

        NotificationModel::notifyUser(
            $payment->booking->user_id,
            'payment',
            'Payment Proof Uploaded',
            "Your payment proof for booking {$payment->booking->reference_no} was uploaded. Our team is verifying your receipt.",
            ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
        );
        NotificationModel::notifyAdminsAndStaff(
            'payment',
            'Payment Proof Uploaded',
            Auth::user()->name . " uploaded payment proof for booking {$payment->booking->reference_no} (₱" . number_format($payment->amount, 2) . ').',
            ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
        );

        return redirect()->route('tourist.bookings')->with('success', 'Payment proof uploaded. Our staff will review and confirm your booking shortly.');
    }

    public function reviews()
    {
        $user = Auth::user();

        $myReviews = Review::with('booking.accommodationUnit')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $reviewedBookingIds = $myReviews->pluck('booking_id')->toArray();
        $myBookings = Booking::with('accommodationUnit')
            ->where('user_id', $user->id)
            ->whereIn('status', [Booking::STATUS_PAID, Booking::STATUS_CHECKED_OUT, Booking::STATUS_COMPLETED])
            ->whereNotIn('id', $reviewedBookingIds)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('tourist.reviews', compact('myReviews', 'myBookings'));
    }

    public function storeReview(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:bookings,id',
            'rating'     => 'required|integer|min:1|max:5',
            'comment'    => 'required|string|max:1000',
        ]);

        $booking = Booking::findOrFail($request->booking_id);

        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($booking->status, [Booking::STATUS_PAID, Booking::STATUS_CHECKED_OUT, Booking::STATUS_COMPLETED], true)) {
            return back()->with('error', 'You can only review bookings that have been paid or completed.');
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            return back()->with('error', 'You have already submitted a review for this booking.');
        }

        $hasProfanity = Review::containsProfanity($request->comment);

        $review = Review::create([
            'user_id'            => Auth::id(),
            'booking_id'         => $booking->id,
            'rating'             => $request->rating,
            'comment'            => $request->comment,
            'is_approved'        => true,
            'is_comment_blocked' => $hasProfanity,
            'block_reason'       => $hasProfanity ? 'Automatically blocked: Inappropriate language / profanity detected' : null,
            'comment_blocked_at' => $hasProfanity ? now() : null,
        ]);

        AuditLog::log('review_submitted', $review, null, $review->toArray());

        if ($hasProfanity) {
            NotificationModel::notifyUser(
                $booking->user_id,
                'review',
                'Review Published (Comment Hidden)',
                "Thank you for reviewing your stay. Your {$review->rating}-star rating has been published. Your written comment was hidden due to inappropriate language.",
                ['review_id' => $review->id]
            );
            NotificationModel::notifyAdminsAndStaff(
                'review',
                'Guest Review Submitted (Inappropriate Language)',
                Auth::user()->name . " submitted a {$review->rating}-star review for stay {$booking->reference_no}. The comment was automatically blocked.",
                ['review_id' => $review->id]
            );

            return redirect()->route('tourist.reviews')->with('success', "Thank you. Your {$review->rating}-star rating has been published. Your comment was hidden due to inappropriate words.");
        }

        NotificationModel::notifyUser(
            $booking->user_id,
            'review',
            'Review Published',
            "Thank you for reviewing your stay. Your {$review->rating}-star review for stay {$booking->reference_no} is now live.",
            ['review_id' => $review->id]
        );
        NotificationModel::notifyAdminsAndStaff(
            'review',
            'New Guest Review Received',
            Auth::user()->name . " submitted a {$review->rating}-star review for stay {$booking->reference_no}.",
            ['review_id' => $review->id]
        );

        return redirect()->route('tourist.reviews')->with('success', 'Thank you. Your review has been published.');
    }

    private function occasionNote(Request $request): ?string
    {
        $notes = trim((string) $request->input('special_requests'));
        $occasion = trim((string) $request->input('occasion'));
        if ($occasion !== '' && ! str_starts_with($notes, 'Occasion:')) {
            $notes = 'Occasion: '.$occasion.($notes !== '' ? "\n\n".$notes : '');
        }

        return $notes !== '' ? $notes : null;
    }
}
