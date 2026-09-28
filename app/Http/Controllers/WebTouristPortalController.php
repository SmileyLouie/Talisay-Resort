<?php

namespace App\Http\Controllers;

use App\Exceptions\BookingConflictException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\AccommodationUnit;
use App\Models\NotificationModel;
use App\Models\Payment;
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
        $user = Auth::user();

        $activeBookings = Booking::with(['accommodationUnit', 'payment'])
            ->where('user_id', $user->id)
            ->whereIn('status', Booking::ACTIVE_STATUSES)
            ->orderBy('booking_date')
            ->get();

        $completedCount = Booking::where('user_id', $user->id)
            ->where('status', Booking::STATUS_COMPLETED)
            ->count();

        $reviewsCount = Review::where('user_id', $user->id)->count();

        $accommodationUnits = AccommodationUnit::available()
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        return view('tourist.dashboard', compact(
            'activeBookings',
            'completedCount',
            'reviewsCount',
            'accommodationUnits'
        ));
    }

    public function bookings()
    {
        $user = Auth::user();

        $bookings = Booking::with(['accommodationUnit', 'payment'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $accommodationUnits = AccommodationUnit::available()->orderBy('unit_number')->get();

        return view('tourist.bookings', compact('bookings', 'accommodationUnits'));
    }

    public function storeBooking(Request $request)
    {
        $request->validate([
            'accommodation_unit_id' => 'required|exists:accommodation_units,id',
            'booking_date'          => 'required|date|after_or_equal:today',
            'check_out_date'        => 'nullable|date|after:booking_date',
            'guests_count'          => 'required|integer|min:1|max:50',
            'special_requests'      => 'nullable|string|max:500',
            'payment_method'        => 'required|in:gcash,paypal,card,cash',
        ]);

        $unit = AccommodationUnit::findOrFail($request->accommodation_unit_id);
        $user = Auth::user();

        try {
            $booking = $this->bookings->createRegular([
                'unit'             => $unit,
                'check_in'         => $request->booking_date,
                'check_out'        => $request->check_out_date,
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $request->special_requests,
                'payment_method'   => $request->payment_method,
                'booking_source'   => 'online',
            ]);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
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

        $msg = match ($request->payment_method) {
            'gcash' => "Booking {$booking->reference_no} created for {$unit->unit_number}. Please upload your GCash receipt to confirm.",
            'cash'  => "Booking {$booking->reference_no} created for {$unit->unit_number}. Please pay on arrival and present your reference number at the front desk.",
            default => "Booking {$booking->reference_no} created for {$unit->unit_number}. Please complete payment to confirm your reservation.",
        };

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
            $this->bookings->cancel($booking, $request->input('cancellation_reason'), 'booking_cancelled_by_tourist');
        } catch (InvalidBookingTransitionException $e) {
            return back()->with('error', $e->getMessage());
        }

        NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Cancelled',
            "Your reservation {$booking->reference_no} has been cancelled.",
            ['booking_id' => $booking->id]
        );
        NotificationModel::notifyAdminsAndStaff(
            'booking',
            'Reservation Cancelled by Guest',
            Auth::user()->name . " cancelled reservation {$booking->reference_no}.",
            ['booking_id' => $booking->id]
        );

        return redirect()->route('tourist.bookings')
            ->with('success', "Reservation {$booking->reference_no} has been cancelled successfully.");
    }

    public function storeSpecialResortBooking(Request $request)
    {
        $request->validate([
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
            'guests_count'     => 'required|integer|min:1|max:200',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method'   => 'required|in:gcash,paypal,card,cash',
        ]);

        $user = Auth::user();

        try {
            $booking = $this->bookings->createSpecialResort([
                'check_in'         => $request->check_in_date,
                'check_out'        => $request->check_out_date,
                'guests_count'     => $request->guests_count,
                'user_id'          => $user->id,
                'special_requests' => $request->special_requests,
                'payment_method'   => $request->payment_method,
            ]);
        } catch (BookingConflictException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

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

        return redirect()->route('tourist.bookings')
            ->with('success', "Special full-resort reservation {$booking->reference_no} submitted. Management will review it within 24 hours.");
    }

    public function checkAvailability(Request $request, AccommodationUnit $unit)
    {
        [$checkIn, $checkOut, $nights] = Booking::normaliseStay(
            $request->query('check_in', now()->format('Y-m-d')),
            $request->query('check_out', now()->addDay()->format('Y-m-d'))
        );

        $conflict    = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut);
        $isAvailable = ($conflict === null) && $unit->is_available;

        return response()->json([
            'available'        => $isAvailable,
            'unit_id'          => $unit->id,
            'unit_name'        => $unit->unit_number,
            'check_in'         => $checkIn,
            'check_out'        => $checkOut,
            'nights'           => $nights,
            'price_per_night'  => (float) $unit->price_per_night,
            'total_amount'     => (float) ($unit->price_per_night * $nights),
            'conflict_message' => $conflict ? "{$unit->unit_number} is already booked on this date." : null,
            'booked_ranges'    => Booking::getBookedDateRangesForUnit($unit->id),
        ]);
    }

    public function uploadPaymentProof(Request $request, Payment $payment)
    {
        Gate::authorize('uploadProof', $payment);

        $request->validate([
            'proof_image' => 'required|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        if ($payment->status === 'success') {
            return redirect()->route('tourist.bookings')->with('error', 'This payment has already been verified.');
        }

        if ($payment->proof_path) {
            Storage::disk('public')->delete($payment->proof_path);
        }

        $payment->update([
            'proof_path'      => $request->file('proof_image')->store('payments', 'public'),
            'payment_channel' => $payment->payment_channel ?? 'gcash',
            'status'          => 'pending',
        ]);

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
}
