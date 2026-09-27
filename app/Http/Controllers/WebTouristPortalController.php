<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\AccommodationUnit;
use App\Models\Payment;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class WebTouristPortalController extends WebControllers
{
    // ─── 1. Tourist Dashboard ─────────────────────────────────
    public function dashboard()
    {
        $user = Auth::user();

        $activeBookings = Booking::with(['accommodationUnit', 'payment'])
            ->where('user_id', $user->id)
            ->whereIn('status', ['pending', 'paid', 'checked_in'])
            ->orderBy('booking_date')
            ->get();

        $completedCount = Booking::where('user_id', $user->id)
            ->where('status', 'completed')
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

    // ─── 2. My Bookings View ──────────────────────────────────
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

    // ─── 3. Store Online Booking ──────────────────────────────
    public function storeBooking(Request $request)
    {
        $request->validate([
            'accommodation_unit_id' => 'required|exists:accommodation_units,id',
            'booking_date'          => 'required|date|after_or_equal:today',
            'check_out_date'        => 'nullable|date|after:booking_date',
            'guests_count'          => 'required|integer|min:1',
            'special_requests'      => 'nullable|string|max:500',
            'payment_method'        => 'required|in:gcash,paypal,card,cash',
        ]);

        $unit = AccommodationUnit::findOrFail($request->accommodation_unit_id);
        $user = Auth::user();

        if (!$unit->is_available) {
            return back()->with('error', "{$unit->unit_number} is currently marked unavailable by management.")->withInput();
        }

        if ($request->guests_count > $unit->max_occupancy) {
            return back()->with('error', "Guests count exceeds capacity limit of {$unit->max_occupancy} guests for {$unit->unit_number}.")->withInput();
        }

        $bookingDate  = $request->booking_date;
        $checkOutDate = $request->check_out_date ?? \Carbon\Carbon::parse($bookingDate)->addDay()->format('Y-m-d');
        $nights = (int) \Carbon\Carbon::parse($bookingDate)->diffInDays(\Carbon\Carbon::parse($checkOutDate));
        if ($nights < 1) $nights = 1;

        // ─── CONFLICT DETECTION: Check if unit or full resort is already booked for these dates ───
        $conflict = Booking::getConflictingBooking($unit->id, $bookingDate, $checkOutDate);
        if ($conflict) {
            $conflictIn  = $conflict->check_in_date ? $conflict->check_in_date->format('M d, Y') : $conflict->booking_date->format('M d, Y');
            $conflictOut = $conflict->check_out_date ? $conflict->check_out_date->format('M d, Y') : \Carbon\Carbon::parse($conflictIn)->addDay()->format('M d, Y');
            
            if ($conflict->booking_type === 'special_resort') {
                return back()->with('error', "Reservation conflict: An exclusive Full-Resort booking is active from {$conflictIn} to {$conflictOut}. Please choose other dates.")->withInput();
            }

            return back()->with('error', "Reservation conflict: {$unit->unit_number} is already booked from {$conflictIn} to {$conflictOut} (Ref: {$conflict->reference_no}). Please select different dates or choose another room/cottage.")->withInput();
        }

        $totalAmount   = $unit->price_per_night * $nights;
        $paymentMethod = $request->payment_method;

        $booking = Booking::create([
            'reference_no'          => Booking::generateReferenceNo(),
            'user_id'               => $user->id,
            'accommodation_unit_id' => $unit->id,
            'booking_date'          => $bookingDate,
            'check_in_date'         => $bookingDate,
            'check_out_date'        => $checkOutDate,
            'nights_count'          => $nights,
            'time_slot'             => '2:00 PM Check-in · 11:00 AM Check-out',
            'guests_count'          => $request->guests_count,
            'status'                => 'pending',
            'total_amount'          => $totalAmount,
            'special_requests'      => $request->special_requests,
        ]);

        // Create payment record based on channel
        $isCash = $paymentMethod === 'cash';
        Payment::create([
            'booking_id'         => $booking->id,
            'amount'             => $totalAmount,
            'gateway'            => $paymentMethod,
            'payment_channel'    => $paymentMethod,
            'transaction_id'     => $isCash ? null : 'TXN-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 10)),
            'status'             => 'pending',
            'is_cash_on_arrival' => $isCash,
        ]);

        // Update capacity
        $capacity = CapacitySchedule::getCapacityForDate($bookingDate);
        $capacity->increment('current_count', $request->guests_count);

        AuditLog::log('booking_created_by_tourist', $booking, null, $booking->toArray());

        // Notifications
        \App\Models\NotificationModel::notifyUser(
            $user->id,
            'booking',
            'Reservation Submitted',
            "Your reservation for {$unit->unit_number} ({$booking->reference_no}) has been received! Please complete payment to secure your stay.",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );
        \App\Models\NotificationModel::notifyAdminsAndStaff(
            'booking',
            'New Reservation Received',
            "{$user->name} reserved {$unit->unit_number} from " . $booking->booking_date->format('M d') . " to " . $booking->check_out_date->format('M d, Y') . " (Ref: {$booking->reference_no}).",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        $msg = match($paymentMethod) {
            'gcash' => "Booking {$booking->reference_no} created for {$unit->unit_number}! Please upload your GCash receipt to confirm.",
            'cash'  => "Booking {$booking->reference_no} created for {$unit->unit_number}! Please pay on arrival. Present your reference number to our front desk.",
            default => "Booking {$booking->reference_no} created for {$unit->unit_number}! Please complete payment to confirm your reservation.",
        };

        return redirect()->route('tourist.bookings')->with('success', $msg);
    }

    // ─── 4. Modify Booking (Change Room/Cottage) ──────────────
    public function modifyBooking(Request $request, Booking $booking)
    {
        // Security check — only the booking owner can modify
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Only active bookings can be modified
        if (!in_array($booking->status, ['pending', 'paid', 'checked_in'])) {
            return back()->with('error', 'Only active bookings can be modified.');
        }

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

        if (!$newUnit->is_available) {
            return back()->with('error', "{$newUnit->unit_number} is currently marked unavailable by management.");
        }

        if ($booking->guests_count > $newUnit->max_occupancy) {
            return back()->with('error', "Your group of {$booking->guests_count} guests exceeds the capacity of {$newUnit->unit_number} ({$newUnit->max_occupancy} max).");
        }

        // ─── CONFLICT CHECK FOR TARGET UNIT ───
        $checkIn  = $booking->check_in_date ? $booking->check_in_date->format('Y-m-d') : $booking->booking_date->format('Y-m-d');
        $checkOut = $booking->check_out_date ? $booking->check_out_date->format('Y-m-d') : \Carbon\Carbon::parse($checkIn)->addDays($booking->nights_count ?: 1)->format('Y-m-d');

        $conflict = Booking::getConflictingBooking($newUnit->id, $checkIn, $checkOut, $booking->id);
        if ($conflict) {
            $conflictIn  = $conflict->check_in_date ? $conflict->check_in_date->format('M d, Y') : $conflict->booking_date->format('M d, Y');
            $conflictOut = $conflict->check_out_date ? $conflict->check_out_date->format('M d, Y') : \Carbon\Carbon::parse($conflictIn)->addDay()->format('M d, Y');
            return back()->with('error', "Cannot switch: {$newUnit->unit_number} is already reserved by another guest from {$conflictIn} to {$conflictOut} (Ref: {$conflict->reference_no}). Please choose another unit.");
        }

        $oldUnit   = $booking->accommodationUnit;
        $oldAmount = $booking->total_amount;
        $nights    = $booking->nights_count > 0 ? $booking->nights_count : 1;
        $newAmount = $newUnit->price_per_night * $nights;
        $priceDiff = $newAmount - $oldAmount;

        DB::transaction(function () use ($booking, $newUnit, $oldUnit, $newAmount, $priceDiff, $request) {
            $old = $booking->toArray();

            $booking->update([
                'original_accommodation_unit_id' => $booking->original_accommodation_unit_id ?? $booking->accommodation_unit_id,
                'accommodation_unit_id'          => $newUnit->id,
                'total_amount'                   => $newAmount,
                'modified_at'                    => now(),
                'modification_notes'             => $request->modification_notes,
            ]);

            // Update payment amount if exists
            if ($booking->payment) {
                $booking->payment->update(['amount' => $newAmount]);
            }

            AuditLog::log('booking_modified_by_tourist', $booking, $old, $booking->fresh()->toArray());
        });

        // Notifications
        \App\Models\NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Modified',
            "Your booking {$booking->reference_no} has been switched to {$newUnit->unit_number}.",
            ['booking_id' => $booking->id]
        );
        \App\Models\NotificationModel::notifyAdminsAndStaff(
            'booking',
            'Booking Modified by Guest',
            "{$booking->user->name} switched booking {$booking->reference_no} to {$newUnit->unit_number}.",
            ['booking_id' => $booking->id]
        );

        $diffMsg = $priceDiff > 0
            ? "An additional ₱" . number_format($priceDiff, 2) . " is due. Please re-upload payment proof."
            : ($priceDiff < 0 ? "A refund of ₱" . number_format(abs($priceDiff), 2) . " will be processed." : "No price change.");

        return redirect()->route('tourist.bookings')
            ->with('success', "Booking {$booking->reference_no} changed to {$newUnit->unit_number}. {$diffMsg}");
    }

    // ─── 4b. Cancel Booking ──────────────────────────────────
    public function cancelBooking(Request $request, Booking $booking)
    {
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        if (!in_array($booking->status, ['pending', 'paid'])) {
            return back()->with('error', 'Only pending or paid reservations can be cancelled.');
        }

        $old = $booking->toArray();
        $booking->update(['status' => 'cancelled']);

        if ($booking->payment && $booking->payment->status === 'pending') {
            $booking->payment->update(['status' => 'failed']);
        }

        AuditLog::log('booking_cancelled_by_tourist', $booking, $old, $booking->fresh()->toArray());

        // Notifications
        \App\Models\NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Cancelled',
            "Your reservation {$booking->reference_no} has been cancelled.",
            ['booking_id' => $booking->id]
        );
        \App\Models\NotificationModel::notifyAdminsAndStaff(
            'booking',
            'Reservation Cancelled by Guest',
            "{$booking->user->name} cancelled reservation {$booking->reference_no}.",
            ['booking_id' => $booking->id]
        );

        return redirect()->route('tourist.bookings')
            ->with('success', "Reservation {$booking->reference_no} has been cancelled successfully.");
    }

    // ─── 5. Store Special Full-Resort Booking ────────────────
    public function storeSpecialResortBooking(Request $request)
    {
        $request->validate([
            'check_in_date'    => 'required|date|after_or_equal:today',
            'check_out_date'   => 'required|date|after:check_in_date',
            'guests_count'     => 'required|integer|min:1',
            'special_requests' => 'nullable|string|max:1000',
            'payment_method'   => 'required|in:gcash,paypal,card,cash',
        ]);

        $user = Auth::user();
        $checkIn  = $request->check_in_date;
        $checkOut = $request->check_out_date;

        $nights = (int) \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut));
        if ($nights < 1) $nights = 1;

        // ─── CONFLICT CHECK: Verify no active bookings exist for those dates ───
        if (Booking::hasSpecialResortConflict($checkIn, $checkOut)) {
            return back()->with('error', "Cannot request Exclusive Full-Resort booking for {$checkIn} to {$checkOut} because one or more rooms/cottages have existing reservations on those dates.")->withInput();
        }

        $firstUnit = AccommodationUnit::available()->first();
        if (!$firstUnit) {
            return back()->with('error', 'No accommodation units are currently configured in the system.');
        }

        // Auto-calculate price: sum of all active unit rates × nights
        $totalUnitSum = AccommodationUnit::available()->sum('price_per_night');
        $totalAmount = $totalUnitSum * $nights;

        $booking = Booking::create([
            'reference_no'          => 'TBRS-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8)),
            'user_id'               => $user->id,
            'accommodation_unit_id' => $firstUnit->id,
            'booking_date'          => $checkIn,
            'check_in_date'         => $checkIn,
            'check_out_date'        => $checkOut,
            'nights_count'          => $nights,
            'time_slot'             => 'Full Resort Exclusive',
            'guests_count'          => $request->guests_count,
            'status'                => 'pending',
            'booking_type'          => 'special_resort',
            'admin_approval_status' => 'pending',
            'total_amount'          => $totalAmount,
            'special_requests'      => $request->special_requests,
        ]);

        Payment::create([
            'booking_id'         => $booking->id,
            'amount'             => $totalAmount,
            'gateway'            => $request->payment_method,
            'payment_channel'    => $request->payment_method,
            'transaction_id'     => null,
            'status'             => 'pending',
            'is_cash_on_arrival' => $request->payment_method === 'cash',
        ]);

        AuditLog::log('special_resort_booking_requested', $booking, null, $booking->toArray());

        // Notifications
        \App\Models\NotificationModel::notifyUser(
            $user->id,
            'booking',
            'Exclusive Resort Booking Submitted',
            "Your exclusive full-resort booking request ({$booking->reference_no}) was submitted! Management will review and confirm availability.",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );
        \App\Models\NotificationModel::notifyAdminsOnly(
            'booking',
            'New Exclusive Resort Booking Request',
            "{$user->name} requested exclusive full-resort booking for {$checkIn} to {$checkOut} ({$nights} Nights, ₱" . number_format($totalAmount, 2) . ").",
            ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]
        );

        return redirect()->route('tourist.bookings')
            ->with('success', "Special Full-Resort Reservation {$booking->reference_no} submitted! Management will review and contact you within 24 hours.");
    }

    // ─── Real-time Accommodation Date Availability Endpoint ──
    public function checkAvailability(Request $request, AccommodationUnit $unit)
    {
        $checkIn  = $request->query('check_in', now()->format('Y-m-d'));
        $checkOut = $request->query('check_out', now()->addDay()->format('Y-m-d'));

        if ($checkOut <= $checkIn) {
            $checkOut = \Carbon\Carbon::parse($checkIn)->addDay()->format('Y-m-d');
        }

        $nights = (int) \Carbon\Carbon::parse($checkIn)->diffInDays(\Carbon\Carbon::parse($checkOut));
        if ($nights < 1) $nights = 1;

        $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut);
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

    // ─── 6. Upload Payment Proof ──────────────────────────────
    public function uploadPaymentProof(Request $request, Payment $payment)
    {
        $request->validate([
            'proof_image' => 'required|image|max:5120',
        ]);

        if ($payment->booking->user_id !== Auth::id()) {
            abort(403);
        }

        if ($request->hasFile('proof_image')) {
            $path = $request->file('proof_image')->store('payments', 'public');
            $payment->update([
                'proof_path'      => $path,
                'payment_channel' => $payment->payment_channel ?? 'gcash',
            ]);

            // Notifications
            \App\Models\NotificationModel::notifyUser(
                $payment->booking->user_id,
                'payment',
                'Payment Proof Uploaded',
                "Your payment proof for booking {$payment->booking->reference_no} was uploaded. Our team is verifying your receipt.",
                ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
            );
            \App\Models\NotificationModel::notifyAdminsAndStaff(
                'payment',
                'Payment Proof Uploaded',
                "{$payment->booking->user->name} uploaded payment proof for booking {$payment->booking->reference_no} (₱" . number_format($payment->amount, 2) . ").",
                ['booking_id' => $payment->booking_id, 'payment_id' => $payment->id]
            );

            return redirect()->route('tourist.bookings')->with('success', 'Payment proof uploaded! Our staff will review and confirm your booking shortly.');
        }

        return redirect()->route('tourist.bookings')->with('error', 'Failed to upload image.');
    }

    // ─── 7. Reviews View & Store ──────────────────────────────
    public function reviews()
    {
        $user = Auth::user();

        $myReviews = Review::with('booking.accommodationUnit')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Only show PAID or COMPLETED bookings that haven't been reviewed yet in the dropdown
        $reviewedBookingIds = $myReviews->pluck('booking_id')->toArray();
        $myBookings = Booking::with('accommodationUnit')
            ->where('user_id', $user->id)
            ->whereIn('status', ['paid', 'completed'])
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

        // Security: only the booking owner can review
        if ($booking->user_id !== Auth::id()) {
            abort(403);
        }

        // Only paid or completed bookings can be reviewed
        if (!in_array($booking->status, ['paid', 'completed'])) {
            return back()->with('error', 'You can only review bookings that have been paid or completed.');
        }

        // Prevent duplicate reviews for the same booking
        if (Review::where('booking_id', $booking->id)->where('user_id', Auth::id())->exists()) {
            return back()->with('error', 'You have already submitted a review for this booking.');
        }

        $hasProfanity = Review::containsProfanity($request->comment);

        $review = Review::create([
            'user_id'            => Auth::id(),
            'booking_id'         => $booking->id,
            'rating'             => $request->rating,
            'comment'            => $request->comment,
            'is_approved'        => true, // Automatically published!
            'is_comment_blocked' => $hasProfanity,
            'block_reason'       => $hasProfanity ? 'Automatically blocked: Inappropriate language / profanity detected' : null,
            'comment_blocked_at' => $hasProfanity ? now() : null,
        ]);

        AuditLog::log('review_submitted', $review, null, $review->toArray());

        // Notifications
        if ($hasProfanity) {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'review',
                'Review Published (Comment Hidden)',
                "Thank you for reviewing your stay! Your {$review->rating}-star rating has been published. However, your written comment was hidden due to inappropriate language.",
                ['review_id' => $review->id]
            );
            \App\Models\NotificationModel::notifyAdminsAndStaff(
                'review',
                'Guest Review Submitted (Inappropriate Language)',
                "{$booking->user->name} submitted a {$review->rating}-star review for stay {$booking->reference_no}. The comment was automatically blocked for containing inappropriate words.",
                ['review_id' => $review->id]
            );

            return redirect()->route('tourist.reviews')->with('success', "Thank you! Your {$review->rating}-star rating has been published. Note: Your comment was hidden due to inappropriate words.");
        } else {
            \App\Models\NotificationModel::notifyUser(
                $booking->user_id,
                'review',
                'Review Published',
                "Thank you for reviewing your stay! Your {$review->rating}-star review for stay {$booking->reference_no} is now live on our resort portal.",
                ['review_id' => $review->id]
            );
            \App\Models\NotificationModel::notifyAdminsAndStaff(
                'review',
                'New Guest Review Received',
                "{$booking->user->name} submitted a {$review->rating}-star review for stay {$booking->reference_no}.",
                ['review_id' => $review->id]
            );

            return redirect()->route('tourist.reviews')->with('success', 'Thank you! Your review and feedback has been published.');
        }
    }
}
