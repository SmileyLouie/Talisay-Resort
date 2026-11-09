<?php

namespace App\Services;

use App\Events\BookingCreated;
use App\Events\BookingUpdated;
use App\Events\CapacityUpdated;
use App\Exceptions\BookingConflictException;
use App\Exceptions\InvalidBookingTransitionException;
use App\Models\AccommodationUnit;
use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\NotificationModel;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single place for reservation rules shared by the tourist portal, the
 * front-desk (manual) flow and the JSON API:
 *
 *  - availability is checked inside a transaction while the unit row is locked,
 *  - the daily visitor cap is enforced for every night of the stay,
 *  - status changes follow Booking::TRANSITIONS,
 *  - capacity counters are rebuilt from source data after every change.
 */
class BookingService
{
    /**
     * Create a regular (single-unit) reservation together with its payment record.
     *
     * @param array $data {
     *     unit: AccommodationUnit, check_in: string, check_out?: string|null,
     *     guests_count: int, user_id?: int|null, guest_name_manual?: string|null,
     *     guest_contact_manual?: string|null, special_requests?: string|null,
     *     status?: string, booking_source?: string, payment_method: string,
     *     created_by?: int|null
     * }
     *
     * @throws BookingConflictException
     */
    public function createRegular(array $data): Booking
    {
        /** @var AccommodationUnit $unit */
        $unit = $data['unit'];
        $guests = (int) $data['guests_count'];
        $status = $data['status'] ?? Booking::STATUS_PENDING;
        $hourly = ($data['stay_type'] ?? 'night') === 'hour';
        $hourStart = null;
        $hours = null;

        if ($hourly) {
            $hours = (int) ($data['hours_count'] ?? 0);
            $hourStart = (string) ($data['hour_start'] ?? '');
            if ($hours < 1 || $hours > 12) {
                throw new BookingConflictException('Choose between 1 and 12 hours for an hourly stay.');
            }
            if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hourStart)) {
                throw new BookingConflictException('Choose a start time for the hourly stay.');
            }
            $startAt = Carbon::parse(Carbon::parse($data['check_in'])->format('Y-m-d') . ' ' . $hourStart);
            if ($startAt->lt(now())) {
                throw new BookingConflictException('Choose a start time that has not already passed.');
            }
            $endAt = $startAt->copy()->addHours($hours);
            $exclusive = $endAt->copy()->startOfDay();
            if ($endAt->gt($exclusive)) {
                $exclusive->addDay();
            }
            $checkIn = $startAt->format('Y-m-d');
            $checkOut = $exclusive->format('Y-m-d');
            $nights = 0;
            $total = round($unit->hourlyRate() * $hours, 2);
            $timeSlot = $startAt->format('g:i A') . ' – ' . $endAt->format('g:i A') . ' (' . $hours . ' ' . ($hours === 1 ? 'hour' : 'hours') . ')';
        } else {
            [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out'] ?? null);
            $total = round((float) $unit->price_per_night * $nights, 2);
            $timeSlot = $data['time_slot'] ?? Booking::DEFAULT_TIME_SLOT;
        }

        $addons = $this->activityAddons($data['activity_ids'] ?? []);
        $total = round($total + $addons->sum(fn ($row) => (float) $row->price), 2);

        if (!$unit->is_available) {
            throw new BookingConflictException("{$unit->unit_number} is currently marked unavailable by management.");
        }

        if ($guests > $unit->max_occupancy) {
            throw new BookingConflictException("Guests count exceeds the capacity limit of {$unit->max_occupancy} guests for {$unit->unit_number}.");
        }

        $booking = DB::transaction(function () use ($unit, $checkIn, $checkOut, $nights, $guests, $status, $data, $hourly, $hourStart, $hours, $total, $timeSlot, $addons) {
            // Serialise concurrent attempts on the same unit.
            AccommodationUnit::whereKey($unit->id)->lockForUpdate()->first();

            $this->assertUnitAvailable($unit, $checkIn, $checkOut, null, $hourly ? $hourStart : null, $hourly ? $hours : null);
            $this->assertDailyCapacity($checkIn, $checkOut, $guests);

            $booking = Booking::create([
                'reference_no'          => Booking::generateReferenceNo(),
                'booking_type'          => 'regular',
                'booking_source'        => $data['booking_source'] ?? 'online',
                'user_id'               => $data['user_id'] ?? null,
                'guest_name_manual'     => $data['guest_name_manual'] ?? null,
                'guest_contact_manual'  => $data['guest_contact_manual'] ?? null,
                'accommodation_unit_id' => $unit->id,
                'booking_date'          => $checkIn,
                'check_in_date'         => $checkIn,
                'check_out_date'        => $checkOut,
                'nights_count'          => $nights,
                'stay_type'             => $hourly ? 'hour' : 'night',
                'hours_count'           => $hourly ? $hours : null,
                'hour_start'            => $hourly ? $hourStart : null,
                'time_slot'             => $timeSlot,
                'guests_count'          => $guests,
                'status'                => $status,
                'total_amount'          => $total,
                'special_requests'      => $data['special_requests'] ?? null,
            ]);

            foreach ($addons as $addon) {
                $booking->activities()->create([
                    'resort_activity_id' => $addon->id,
                    'title' => $addon->title,
                    'price' => $addon->price ?? 0,
                    'quantity' => 1,
                ]);
            }

            $method = $data['payment_method'];
            $isCash = PaymentMethod::isCash($method);
            $paidUpFront = in_array($status, [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN], true);
            [$plan, $due, $balance] = $this->payableAmounts($total, $method, $data);

            Payment::create([
                'booking_id'         => $booking->id,
                'amount'             => $due,
                'gateway'            => $method,
                'payment_channel'    => $method,
                'transaction_id'     => $this->transactionId($data['booking_source'] ?? 'online', $isCash),
                'card_last_four'     => PaymentMethod::isCard($method) ? ($data['card_last_four'] ?? null) : null,
                'status'             => $paidUpFront ? 'success' : 'pending',
                'is_cash_on_arrival' => $isCash,
                'metadata'           => $this->paymentMetadata($data, $method, $plan, $total, $balance),
            ]);

            CapacitySchedule::recalculateForBooking($booking);

            return $booking;
        });

        $this->broadcastCapacity($booking);
        broadcast(new BookingCreated($booking))->toOthers();

        return $booking;
    }

    /**
     * Move a booking to a new status, enforcing the transition table and
     * keeping payment + capacity consistent.
     *
     * @throws InvalidBookingTransitionException
     * @throws BookingConflictException when reinstating into an occupied slot
     */
    public function changeStatus(Booking $booking, string $newStatus, array $context = []): Booking
    {
        $oldStatus = $booking->status;

        if ($newStatus === $oldStatus) {
            return $booking;
        }

        if (!$booking->canTransitionTo($newStatus)) {
            $from = str_replace('_', ' ', $oldStatus);
            $to   = str_replace('_', ' ', $newStatus);
            throw new InvalidBookingTransitionException("A {$from} reservation cannot be changed to {$to}.");
        }

        if ($oldStatus === Booking::STATUS_CANCELLED && $booking->payment?->refundSent()) {
            throw new InvalidBookingTransitionException('This booking cannot be reopened because its payment was already refunded to the guest. Create a new booking instead.');
        }

        DB::transaction(function () use ($booking, $newStatus, $oldStatus, $context) {
            $attributes = ['status' => $newStatus];

            if ($newStatus === Booking::STATUS_CANCELLED) {
                $attributes['cancelled_at']        = now();
                $attributes['cancellation_reason'] = $context['reason'] ?? 'Cancelled';
            }

            if ($oldStatus === Booking::STATUS_CANCELLED) {
                // Reinstating: the slot may have been taken in the meantime.
                if ($booking->isSpecialResort()) {
                    if (Booking::hasSpecialResortConflict($booking->checkInDate(), $booking->checkOutDate(), $booking->id)) {
                        throw new BookingConflictException('The resort already has reservations on those dates; the full-resort booking cannot be reinstated.');
                    }
                } else {
                    AccommodationUnit::whereKey($booking->accommodation_unit_id)->lockForUpdate()->first();
                    $this->assertUnitAvailable($booking->accommodationUnit, $booking->checkInDate(), $booking->checkOutDate(), $booking->id);
                    $this->assertDailyCapacity($booking->checkInDate(), $booking->checkOutDate(), (int) $booking->guests_count, $booking->id);
                }
                $attributes['cancelled_at']        = null;
                $attributes['cancellation_reason'] = null;
            }

            $booking->update($attributes);

            $payment = $booking->payment;
            if ($payment) {
                if ($oldStatus === Booking::STATUS_CANCELLED && $newStatus !== Booking::STATUS_CANCELLED) {
                    $this->restorePaymentAfterReinstatement($payment);
                } elseif (in_array($newStatus, [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN], true) && $payment->status === 'pending') {
                    $payment->update(['status' => 'success']);
                } elseif ($newStatus === Booking::STATUS_CANCELLED) {
                    $this->settleCancellationPayment($booking, $payment);
                }
            }

            CapacitySchedule::recalculateForBooking($booking);

            AuditLog::log(
                $context['audit_action'] ?? 'booking_status_changed',
                $booking,
                ['status' => $oldStatus],
                ['status' => $newStatus] + (isset($context['reason']) ? ['reason' => $context['reason']] : [])
            );
        });

        $fresh = $booking->fresh();
        $this->broadcastCapacity($fresh);
        broadcast(new BookingUpdated($fresh));
        app(SupabaseSyncService::class)->pushBooking($fresh);

        return $fresh;
    }

    public function cancel(Booking $booking, ?string $reason, string $auditAction = 'booking_cancelled'): Booking
    {
        return $this->changeStatus($booking, Booking::STATUS_CANCELLED, [
            'reason'       => $reason ?: 'Cancelled',
            'audit_action' => $auditAction,
        ]);
    }

    /**
     * What a cancellation would do to a payment that is already confirmed.
     * 48 hours or more before the stay: full refund of the amount paid.
     * 24 to 48 hours: the late-cancellation percent from System Settings.
     * Under 24 hours: the same-day percent from System Settings.
     * A payment that is still waiting for approval is not refunded.
     *
     * @return array{paid: float, percent: int, refund: float, kept: float, rule: string, message: string}
     */
    public function previewCancellation(Booking $booking): array
    {
        $payment = $booking->payment;
        $paid = ($payment && $payment->status === 'success') ? round((float) $payment->amount, 2) : 0.0;
        $start = $booking->stayWindow()[0];
        $hoursUntil = ($start->getTimestamp() - now()->getTimestamp()) / 3600;

        if (!$payment || $payment->status !== 'success' || $paid <= 0) {
            return [
                'paid' => 0.0,
                'percent' => 0,
                'refund' => 0.0,
                'kept' => 0.0,
                'rule' => 'unpaid',
                'message' => 'No payment has been confirmed yet, so cancelling does not create a refund. The room or cottage is released for other guests.',
            ];
        }

        if ($hoursUntil >= 48) {
            $percent = 100;
            $rule = 'full';
        } elseif ($hoursUntil >= 24) {
            $percent = max(0, min(100, (int) setting('late_cancellation_refund_percent', 50)));
            $rule = 'partial';
        } else {
            $percent = max(0, min(100, (int) setting('same_day_cancellation_refund', 0)));
            $rule = $percent > 0 ? 'partial' : 'none';
        }

        $refund = round($paid * $percent / 100, 2);
        $kept = round($paid - $refund, 2);

        if ($percent >= 100) {
            $message = 'A full refund of ₱' . number_format($refund, 2) . ' will be recorded. The resort returns that amount to the guest.';
        } elseif ($refund > 0) {
            $message = 'A ' . $percent . '% refund of ₱' . number_format($refund, 2) . ' will be recorded. The resort keeps ₱' . number_format($kept, 2) . '.';
        } else {
            $message = 'The stay starts in under 24 hours, so the ₱' . number_format($paid, 2) . ' payment is not refunded.';
        }

        return compact('paid', 'percent', 'refund', 'kept', 'rule') + ['message' => $message];
    }

    public function describeCancellation(Booking $booking): string
    {
        $payment = $booking->payment;
        $rule = $payment->metadata['refund_rule'] ?? null;

        if (!$payment || $rule === 'unpaid' || $payment->status === 'failed') {
            return 'No payment was confirmed, so nothing is refunded.';
        }

        $refund = round((float) ($payment->metadata['refund_amount'] ?? 0), 2);
        $percent = (int) ($payment->metadata['refund_percent'] ?? 0);
        $kept = round((float) ($payment->metadata['kept_amount'] ?? $payment->amount), 2);

        if ($percent >= 100 && $refund > 0) {
            return 'A full refund of ₱' . number_format($refund, 2) . ' is recorded for the resort to return.';
        }

        if ($refund > 0) {
            return 'A ' . $percent . '% refund of ₱' . number_format($refund, 2) . ' is recorded. The resort keeps ₱' . number_format($kept, 2) . '.';
        }

        if (($payment->metadata['paid_amount'] ?? 0) > 0) {
            return 'The payment is not refunded because the cancellation is within 24 hours of the stay.';
        }

        return 'The room or cottage is released for other guests.';
    }

    private function settleCancellationPayment(Booking $booking, Payment $payment): void
    {
        $preview = $this->previewCancellation($booking);
        $metadata = $payment->metadata ?? [];
        $metadata['payment_status_before_cancel'] = $payment->status;
        $metadata['paid_amount'] = $preview['paid'] > 0 ? $preview['paid'] : round((float) $payment->amount, 2);
        $metadata['refund_percent'] = $preview['percent'];
        $metadata['refund_amount'] = $preview['refund'];
        $metadata['kept_amount'] = $preview['kept'];
        $metadata['refund_rule'] = $preview['rule'];
        $metadata['refunded_at'] = now()->toIso8601String();

        $changes = ['metadata' => $metadata];

        if ($preview['rule'] === 'unpaid' && $payment->status === 'pending') {
            $changes['status'] = 'failed';
        } elseif ($preview['percent'] >= 100 && $preview['refund'] > 0) {
            $changes['status'] = 'refunded';
            $changes['amount'] = $preview['paid'];
        } elseif ($preview['refund'] > 0) {
            $changes['amount'] = $preview['kept'];
        }

        $payment->update($changes);
    }

    private function restorePaymentAfterReinstatement(Payment $payment): void
    {
        $metadata = $payment->metadata ?? [];
        $previous = $metadata['payment_status_before_cancel'] ?? null;

        if ($previous === null) {
            return;
        }

        $paid = $metadata['paid_amount'] ?? $payment->amount;
        unset(
            $metadata['payment_status_before_cancel'],
            $metadata['paid_amount'],
            $metadata['refund_percent'],
            $metadata['refund_amount'],
            $metadata['kept_amount'],
            $metadata['refund_rule'],
            $metadata['refunded_at']
        );

        $payment->update([
            'status' => $previous,
            'amount' => $paid,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Exclusive full-resort request. Inventory is reserved by the conflict
     * rules immediately; the daily visitor cap is only locked after approval.
     *
     * @throws BookingConflictException
     */
    public function createSpecialResort(array $data): Booking
    {
        [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out']);
        $guests = (int) $data['guests_count'];

        $booking = DB::transaction(function () use ($checkIn, $checkOut, $nights, $guests, $data) {
            if (Booking::hasSpecialResortConflict($checkIn, $checkOut)) {
                throw new BookingConflictException(
                    "Cannot request an exclusive full-resort booking from {$checkIn} to {$checkOut} because one or more rooms or cottages already have reservations on those dates."
                );
            }

            $addons = $this->activityAddons($data['activity_ids'] ?? []);
            $total = round($this->exclusiveStayTotal($nights) + $addons->sum(fn ($row) => (float) $row->price), 2);
            $method = $data['payment_method'];
            [$plan, $due, $balance] = $this->payableAmounts($total, $method, $data);

            $booking = Booking::create([
                'reference_no'          => Booking::generateReferenceNo('TBRS'),
                'booking_type'          => 'special_resort',
                'booking_source'        => $data['booking_source'] ?? 'online',
                'user_id'               => $data['user_id'] ?? null,
                'guest_name_manual'     => $data['guest_name_manual'] ?? null,
                'guest_contact_manual'  => $data['guest_contact_manual'] ?? null,
                'accommodation_unit_id' => null,
                'booking_date'          => $checkIn,
                'check_in_date'         => $checkIn,
                'check_out_date'        => $checkOut,
                'nights_count'          => $nights,
                'time_slot'             => 'Full Resort Exclusive',
                'guests_count'          => $guests,
                'status'                => Booking::STATUS_PENDING,
                'admin_approval_status' => 'pending',
                'total_amount'          => $total,
                'special_requests'      => $data['special_requests'] ?? null,
            ]);

            foreach ($addons as $addon) {
                $booking->activities()->create([
                    'resort_activity_id' => $addon->id,
                    'title' => $addon->title,
                    'price' => $addon->price ?? 0,
                    'quantity' => 1,
                ]);
            }

            Payment::create([
                'booking_id'         => $booking->id,
                'amount'             => $due,
                'gateway'            => $method,
                'payment_channel'    => $method,
                'transaction_id'     => $this->transactionId('online', PaymentMethod::isCash($method)),
                'card_last_four'     => PaymentMethod::isCard($method) ? ($data['card_last_four'] ?? null) : null,
                'status'             => 'pending',
                'is_cash_on_arrival' => PaymentMethod::isCash($method),
                'metadata'           => $this->paymentMetadata($data, $method, $plan, $total, $balance),
            ]);

            return $booking;
        });

        broadcast(new BookingCreated($booking))->toOthers();

        return $booking;
    }

    /**
     * Change dates, guest count, or notes on an exclusive full-resort stay.
     * Does not cancel the reservation.
     */
    public function updateSpecialResort(Booking $booking, array $data): Booking
    {
        if (! $booking->isSpecialResort()) {
            throw new InvalidBookingTransitionException('This is not an exclusive full-resort booking.');
        }

        if (in_array($booking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_CHECKED_OUT, Booking::STATUS_COMPLETED], true)) {
            throw new InvalidBookingTransitionException('This exclusive booking can no longer be edited.');
        }

        [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out']);
        $guests = max(1, min(200, (int) $data['guests_count']));

        DB::transaction(function () use ($booking, $checkIn, $checkOut, $nights, $guests, $data) {
            if (Booking::hasSpecialResortConflict($checkIn, $checkOut, $booking->id)) {
                throw new BookingConflictException(
                    "Those dates overlap another reservation. Choose other dates for this exclusive stay."
                );
            }

            $total = $this->exclusiveStayTotal($nights);
            $old = $booking->toArray();
            $updates = [
                'booking_date'       => $checkIn,
                'check_in_date'      => $checkIn,
                'check_out_date'     => $checkOut,
                'nights_count'       => $nights,
                'guests_count'       => $guests,
                'total_amount'       => $total,
                'special_requests'   => $data['special_requests'] ?? $booking->special_requests,
                'modified_at'        => now(),
                'modification_notes' => $data['modification_notes'] ?? 'Exclusive stay updated by staff.',
            ];
            if (array_key_exists('guest_name_manual', $data)) {
                $updates['guest_name_manual'] = $data['guest_name_manual'] ?: $booking->guest_name_manual;
            }
            if (array_key_exists('guest_contact_manual', $data)) {
                $updates['guest_contact_manual'] = $data['guest_contact_manual'] ?: $booking->guest_contact_manual;
            }
            $booking->update($updates);

            if ($booking->payment) {
                $meta = $booking->payment->metadata ?? [];
                $plan = ($meta['payment_plan'] ?? 'full') === 'down' ? 'down' : 'full';
                $due = $plan === 'down' ? round($total * 0.5, 2) : $total;
                $paid = (float) $booking->payment->amount;
                $meta['payment_plan'] = $plan;
                $meta['stay_total'] = $total;
                $meta['balance'] = $booking->payment->status === 'success'
                    ? round(max(0, $total - $paid), 2)
                    : round($total - $due, 2);
                $paymentData = ['metadata' => $meta];
                if ($booking->payment->status === 'pending') {
                    $paymentData['amount'] = $due;
                }
                $booking->payment->update($paymentData);
            }

            CapacitySchedule::recalculateForBooking($booking);
            AuditLog::log('exclusive_booking_updated', $booking, $old, $booking->fresh()->toArray());
        });

        $this->broadcastCapacity($booking);
        $fresh = $booking->fresh(['payment', 'user']);
        broadcast(new BookingUpdated($fresh));
        app(SupabaseSyncService::class)->pushBooking($fresh);

        return $fresh;
    }

    /**
     * Switch an active reservation to another unit. Price increases reopen
     * a pending payment so the guest can settle the difference.
     *
     * @return array{booking: Booking, price_diff: float}
     */
    public function changeUnit(Booking $booking, AccommodationUnit $newUnit, ?string $notes = null): array
    {
        if (!$booking->isActive() || $booking->isSpecialResort()) {
            throw new InvalidBookingTransitionException('Only an active regular reservation can be switched to another unit.');
        }

        if ((int) $newUnit->id === (int) $booking->accommodation_unit_id) {
            throw new BookingConflictException('Please select a different room or cottage.');
        }

        if (!$newUnit->is_available) {
            throw new BookingConflictException("{$newUnit->unit_number} is currently marked unavailable by management.");
        }

        if ($booking->guests_count > $newUnit->max_occupancy) {
            throw new BookingConflictException(
                "Your group of {$booking->guests_count} guests exceeds the capacity of {$newUnit->unit_number} ({$newUnit->max_occupancy} max)."
            );
        }

        $oldAmount = (float) $booking->total_amount;
        $nights    = max(1, (int) $booking->nights_count);
        $newAmount = round((float) $newUnit->price_per_night * $nights, 2);
        $priceDiff = round($newAmount - $oldAmount, 2);

        DB::transaction(function () use ($booking, $newUnit, $newAmount, $priceDiff, $notes) {
            AccommodationUnit::whereKey($newUnit->id)->lockForUpdate()->first();
            $this->assertUnitAvailable($newUnit, $booking->checkInDate(), $booking->checkOutDate(), $booking->id);

            $old = $booking->toArray();
            $booking->update([
                'original_accommodation_unit_id' => $booking->original_accommodation_unit_id ?? $booking->accommodation_unit_id,
                'accommodation_unit_id'          => $newUnit->id,
                'total_amount'                   => $newAmount,
                'modified_at'                    => now(),
                'modification_notes'             => $notes,
            ]);

            if ($booking->payment) {
                $meta = $booking->payment->metadata ?? [];
                $plan = ($meta['payment_plan'] ?? 'full') === 'down' ? 'down' : 'full';
                $due = $plan === 'down' ? round($newAmount * 0.5, 2) : $newAmount;
                $meta['payment_plan'] = $plan;
                $meta['stay_total'] = $newAmount;
                $meta['balance'] = round($newAmount - $due, 2);
                $paymentData = ['amount' => $due, 'metadata' => $meta];
                if ($priceDiff > 0 && $booking->payment->status === 'success') {
                    $paymentData['status'] = 'pending';
                }
                $booking->payment->update($paymentData);
            }

            AuditLog::log('booking_modified_by_tourist', $booking, $old, $booking->fresh()->toArray());
        });

        $fresh = $booking->fresh(['payment', 'accommodationUnit', 'user']);
        broadcast(new BookingUpdated($fresh));

        return ['booking' => $fresh, 'price_diff' => $priceDiff];
    }

    public function approveSpecial(Booking $booking, int $adminId): Booking
    {
        if (!$booking->isSpecialResort()) {
            throw new InvalidBookingTransitionException('This is not a special full-resort booking.');
        }

        if ($booking->admin_approval_status !== 'pending' || $booking->status === Booking::STATUS_CANCELLED) {
            throw new InvalidBookingTransitionException('Only a pending exclusive-resort request can be approved.');
        }

        DB::transaction(function () use ($booking, $adminId) {
            if (Booking::hasSpecialResortConflict($booking->checkInDate(), $booking->checkOutDate(), $booking->id)) {
                throw new BookingConflictException('The requested dates now overlap another reservation. The exclusive request cannot be approved.');
            }

            $booking->update([
                'admin_approval_status' => 'approved',
                'admin_approved_by'     => $adminId,
                'status'                => Booking::STATUS_PAID,
            ]);

            if ($booking->payment) {
                $booking->payment->update(['status' => 'success']);
            }

            CapacitySchedule::recalculateForBooking($booking);
            AuditLog::log('special_resort_booking_approved', $booking, null, $booking->toArray());
        });

        $this->broadcastCapacity($booking);
        broadcast(new BookingUpdated($booking->fresh()));

        return $booking->fresh();
    }

    public function rejectSpecial(Booking $booking, int $adminId, string $reason): Booking
    {
        if (!$booking->isSpecialResort()) {
            throw new InvalidBookingTransitionException('This is not a special full-resort booking.');
        }

        DB::transaction(function () use ($booking, $adminId, $reason) {
            $booking->update([
                'admin_approval_status' => 'rejected',
                'admin_approved_by'     => $adminId,
                'status'                => Booking::STATUS_CANCELLED,
                'cancelled_at'          => now(),
                'cancellation_reason'   => $reason,
            ]);

            if ($booking->payment) {
                $booking->payment->update(['status' => 'failed']);
            }

            CapacitySchedule::recalculateForBooking($booking);
            AuditLog::log('special_resort_booking_rejected', $booking, null, ['reason' => $reason]);
        });

        $this->broadcastCapacity($booking);
        broadcast(new BookingUpdated($booking->fresh()));

        return $booking->fresh();
    }

    public function exclusiveNightlyRate(): float
    {
        $override = \App\Models\SystemSetting::get('exclusive_nightly_rate');
        if ($override !== null && $override !== '' && (float) $override > 0) {
            return round((float) $override, 2);
        }

        $sum = (float) AccommodationUnit::available()->sum('price_per_night');
        if ($sum <= 0) {
            $sum = (float) AccommodationUnit::query()->sum('price_per_night');
        }

        return round($sum, 2);
    }

    private function activityAddons(array $ids): \Illuminate\Support\Collection
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();
        if ($ids->isEmpty() || ! \App\Models\ResortActivity::ready()) {
            return collect();
        }

        return \App\Models\ResortActivity::query()
            ->whereIn('id', $ids)
            ->where('is_active', true)
            ->where('is_bookable', true)
            ->get();
    }

    private function exclusiveStayTotal(int $nights): float
    {
        $sum = $this->exclusiveNightlyRate();
        if ($sum <= 0) {
            throw new BookingConflictException('No accommodation units are currently configured in the system.');
        }

        return round($sum * max(1, $nights), 2);
    }

    /**
     * @return array{0: string, 1: float, 2: float}
     */
    private function payableAmounts(float $total, string $method, array $data): array
    {
        $plan = 'full';
        if (PaymentMethod::allowsDownPayment($method) && ($data['payment_plan'] ?? 'full') === 'down') {
            $plan = 'down';
        }
        $due = $plan === 'down' ? round($total * 0.5, 2) : $total;

        return [$plan, $due, round($total - $due, 2)];
    }

    private function paymentMetadata(array $data, string $method, string $plan, float $total, float $balance): array
    {
        return array_filter([
            'source' => $data['booking_source'] ?? 'online',
            'created_by' => $data['created_by'] ?? null,
            'card_last_four' => PaymentMethod::isCard($method) ? ($data['card_last_four'] ?? null) : null,
            'payment_plan' => $plan,
            'stay_total' => $total,
            'balance' => $balance,
            'gcash_method' => PaymentMethod::needsProof($method) ? (($data['gcash_method'] ?? 'receipt') === 'qr' ? 'qr' : 'receipt') : null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function approvePayment(Payment $payment): Payment
    {
        if ($payment->status !== 'pending') {
            throw new InvalidBookingTransitionException('Payment is not in pending status.');
        }

        $booking = $payment->booking;
        if (!$booking) {
            throw new InvalidBookingTransitionException('This payment is not linked to a reservation.');
        }

        DB::transaction(function () use ($payment, $booking) {
            $payment->update(['status' => 'success']);
            AuditLog::log('payment_approved', $payment);

            // Exclusive bookings stay pending until an administrator approves them.
            if ($booking->isSpecialResort() && $booking->admin_approval_status !== 'approved') {
                return;
            }

            if ($booking->status === Booking::STATUS_PENDING) {
                $this->changeStatus($booking, Booking::STATUS_PAID, ['audit_action' => 'booking_marked_paid_after_payment']);
            }
        });

        $fresh = $payment->fresh(['booking']);
        if ($fresh->booking) {
            app(SupabaseSyncService::class)->pushBooking($fresh->booking);
        }

        return $fresh;
    }

    /**
     * Decline a payment. A booking that was still waiting on this payment is cancelled
     * so the room or cottage is released; a booking that is already paid or checked in is left alone.
     */
    public function rejectPayment(Payment $payment): Payment
    {
        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'failed']);
            AuditLog::log('payment_rejected', $payment);

            $booking = $payment->booking;
            if ($booking && $booking->status === Booking::STATUS_PENDING) {
                $this->cancel($booking, 'Payment declined by the resort.', 'booking_cancelled_payment_declined');
            }
        });

        $fresh = $payment->fresh(['booking']);
        if ($fresh->booking) {
            app(SupabaseSyncService::class)->pushBooking($fresh->booking);
        }

        return $fresh;
    }

    /**
     * Record money returned to the guest. Only for a declined payment the guest actually sent,
     * or a payment on a cancelled booking. The resort sends the money itself (GCash, cash);
     * this keeps the books and the guest in step.
     *
     * @throws InvalidBookingTransitionException
     */
    public function refundPayment(Payment $payment, float $amount, ?string $reference, ?string $note, ?\App\Models\User $by = null): Payment
    {
        $state = $payment->refundState();
        if ($state === 'sent') {
            throw new InvalidBookingTransitionException('A refund was already recorded for this payment.');
        }
        if (!in_array($state, ['due', 'available'], true)) {
            throw new InvalidBookingTransitionException('Only a declined payment or a payment on a cancelled booking can be refunded. Cancel the booking first.');
        }

        $paid = $payment->paidAmount();
        $amount = round($amount, 2);
        if ($amount <= 0 || $amount > $paid) {
            throw new InvalidBookingTransitionException('The refund must be more than ₱0 and no more than the ₱' . number_format($paid, 2) . ' the guest paid.');
        }

        DB::transaction(function () use ($payment, $amount, $paid, $reference, $note, $by) {
            $old = ['status' => $payment->status, 'amount' => (float) $payment->amount];
            $metadata = $payment->metadata ?? [];
            $metadata['paid_amount'] = $paid;
            $metadata['payment_status_before_refund'] = $payment->status;
            $metadata['refund_amount'] = $amount;
            $metadata['refund_percent'] = (int) round($amount / $paid * 100);
            $metadata['kept_amount'] = round($paid - $amount, 2);
            $metadata['refund_rule'] = $metadata['refund_rule'] ?? ($payment->status === 'failed' ? 'declined' : 'manual');
            $metadata['refunded_at'] = now()->toIso8601String();
            $metadata['refund_sent'] = [
                'amount' => $amount,
                'reference' => $reference ?: null,
                'note' => $note ?: null,
                'by' => $by?->name,
                'by_id' => $by?->id,
                'at' => now()->toIso8601String(),
            ];

            if ($payment->status === 'failed') {
                $changes = ['status' => 'refunded', 'amount' => $amount];
            } elseif ($amount >= $paid) {
                $changes = ['status' => 'refunded', 'amount' => $paid];
            } else {
                $changes = ['status' => 'success', 'amount' => round($paid - $amount, 2)];
            }

            $payment->update($changes + ['metadata' => $metadata]);
            AuditLog::log('payment_refunded', $payment, $old, $changes + ['refund' => $amount, 'reference' => $reference]);
        });

        $fresh = $payment->fresh(['booking']);
        if ($fresh->booking) {
            if ($fresh->booking->user_id) {
                NotificationModel::notifyUser(
                    $fresh->booking->user_id,
                    'payment',
                    'Refund sent',
                    'The resort refunded ₱' . number_format($amount, 2) . " for booking {$fresh->booking->reference_no}"
                        . ($reference ? " (reference {$reference})" : '') . '.',
                    ['booking_id' => $fresh->booking_id, 'payment_id' => $fresh->id]
                );
            }
            app(SupabaseSyncService::class)->pushBooking($fresh->booking);
        }

        return $fresh;
    }

    /**
     * Notify the guest (if registered) about a status change.
     */
    public function notifyGuestOfStatus(Booking $booking): void
    {
        if (!$booking->user_id) {
            return;
        }

        NotificationModel::notifyUser(
            $booking->user_id,
            'booking',
            'Reservation Status Updated',
            "Your reservation {$booking->reference_no} is now " . ucfirst(str_replace('_', ' ', $booking->status)) . '.',
            ['booking_id' => $booking->id, 'status' => $booking->status]
        );
    }

    /**
     * Build the user-facing explanation for a conflict found during booking.
     */
    public function describeConflict(AccommodationUnit $unit, Booking $conflict): string
    {
        if ($conflict->isHourly()) {
            return "{$unit->unit_number} is already booked {$conflict->time_slot}.";
        }

        $in  = $conflict->checkInDate()->format('M d, Y');
        $out = $conflict->checkOutDate()->format('M d, Y');

        if ($conflict->isSpecialResort()) {
            return "Reservation conflict: an exclusive full-resort booking is active from {$in} to {$out}. Please choose other dates.";
        }

        return "Reservation conflict: {$unit->unit_number} is already booked from {$in} to {$out} (Ref: {$conflict->reference_no}). Please select different dates or another room/cottage.";
    }

    public function checkStay(AccommodationUnit $unit, $checkIn, $checkOut, int $guests): void
    {
        [$checkIn, $checkOut] = array_slice(Booking::normaliseStay($checkIn, $checkOut), 0, 2);

        if (!$unit->is_available) {
            throw new BookingConflictException("{$unit->unit_number} is currently marked unavailable by management.");
        }

        if ($guests > $unit->max_occupancy) {
            throw new BookingConflictException("Guests count exceeds the capacity limit of {$unit->max_occupancy} guests for {$unit->unit_number}.");
        }

        $this->assertUnitAvailable($unit, $checkIn, $checkOut);
        $this->assertDailyCapacity($checkIn, $checkOut, $guests);
    }

    // ─── internals ───────────────────────────────────────────────────────────

    protected function assertUnitAvailable(AccommodationUnit $unit, $checkIn, $checkOut, ?int $excludeId = null, ?string $hourStart = null, ?int $hours = null): void
    {
        $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut, $excludeId, $hourStart, $hours);
        if ($conflict) {
            throw new BookingConflictException($this->describeConflict($unit, $conflict), $conflict);
        }
    }

    protected function assertDailyCapacity($checkIn, $checkOut, int $guests, ?int $excludeId = null): void
    {
        [$in, $out] = Booking::normaliseStay($checkIn, $checkOut);

        for ($d = \Carbon\Carbon::parse($in); $d->lt(\Carbon\Carbon::parse($out)); $d->addDay()) {
            $day = $d->format('Y-m-d');
            $capacity = CapacitySchedule::getCapacityForDate($day);

            $occupied = (int) Booking::active()->occupyingDate($day)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->sum('guests_count');

            if ($occupied + $guests > (int) $capacity->max_capacity) {
                $remaining = max(0, (int) $capacity->max_capacity - $occupied);
                throw new BookingConflictException(
                    "The resort's daily visitor limit is reached on " . $d->format('M d, Y') . " (only {$remaining} slot(s) left). Please choose another date or reduce the party size."
                );
            }
        }
    }

    protected function transactionId(string $source, bool $isCash): ?string
    {
        $prefix = $isCash ? 'CASH' : ($source === 'manual' ? 'MNL' : 'TXN');

        return $prefix . '-' . strtoupper(bin2hex(random_bytes(5)));
    }

    protected function broadcastCapacity(Booking $booking): void
    {
        foreach ($booking->occupiedDates() as $date) {
            broadcast(new CapacityUpdated(CapacitySchedule::getCapacityForDate($date)));
        }
    }
}
