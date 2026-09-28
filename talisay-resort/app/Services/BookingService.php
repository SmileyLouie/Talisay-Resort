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
        [$checkIn, $checkOut, $nights] = Booking::normaliseStay($data['check_in'], $data['check_out'] ?? null);
        $guests = (int) $data['guests_count'];
        $status = $data['status'] ?? Booking::STATUS_PENDING;

        if (!$unit->is_available) {
            throw new BookingConflictException("{$unit->unit_number} is currently marked unavailable by management.");
        }

        if ($guests > $unit->max_occupancy) {
            throw new BookingConflictException("Guests count exceeds the capacity limit of {$unit->max_occupancy} guests for {$unit->unit_number}.");
        }

        $booking = DB::transaction(function () use ($unit, $checkIn, $checkOut, $nights, $guests, $status, $data) {
            // Serialise concurrent attempts on the same unit.
            AccommodationUnit::whereKey($unit->id)->lockForUpdate()->first();

            $this->assertUnitAvailable($unit, $checkIn, $checkOut);
            $this->assertDailyCapacity($checkIn, $checkOut, $guests);

            $total = round((float) $unit->price_per_night * $nights, 2);

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
                'time_slot'             => $data['time_slot'] ?? Booking::DEFAULT_TIME_SLOT,
                'guests_count'          => $guests,
                'status'                => $status,
                'total_amount'          => $total,
                'special_requests'      => $data['special_requests'] ?? null,
            ]);

            $method = $data['payment_method'];
            $isCash = $method === 'cash';
            $paidUpFront = in_array($status, [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN], true);

            Payment::create([
                'booking_id'         => $booking->id,
                'amount'             => $total,
                'gateway'            => $method,
                'payment_channel'    => $method,
                'transaction_id'     => $this->transactionId($data['booking_source'] ?? 'online', $isCash),
                'status'             => $paidUpFront ? 'success' : 'pending',
                'is_cash_on_arrival' => $isCash,
                'metadata'           => array_filter([
                    'source'     => $data['booking_source'] ?? 'online',
                    'created_by' => $data['created_by'] ?? null,
                ]),
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
                if (in_array($newStatus, [Booking::STATUS_PAID, Booking::STATUS_CHECKED_IN], true) && $payment->status === 'pending') {
                    $payment->update(['status' => 'success']);
                } elseif ($newStatus === Booking::STATUS_CANCELLED && $payment->status === 'pending') {
                    $payment->update(['status' => 'failed']);
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

            $availableUnits = AccommodationUnit::available();
            if (!$availableUnits->exists()) {
                throw new BookingConflictException('No accommodation units are currently configured in the system.');
            }

            $total = round((float) $availableUnits->sum('price_per_night') * $nights, 2);
            $method = $data['payment_method'];

            $booking = Booking::create([
                'reference_no'          => Booking::generateReferenceNo('TBRS'),
                'booking_type'          => 'special_resort',
                'booking_source'        => $data['booking_source'] ?? 'online',
                'user_id'               => $data['user_id'],
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

            Payment::create([
                'booking_id'         => $booking->id,
                'amount'             => $total,
                'gateway'            => $method,
                'payment_channel'    => $method,
                'transaction_id'     => $this->transactionId('online', $method === 'cash'),
                'status'             => 'pending',
                'is_cash_on_arrival' => $method === 'cash',
            ]);

            return $booking;
        });

        broadcast(new BookingCreated($booking))->toOthers();

        return $booking;
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
                $paymentData = ['amount' => $newAmount];
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

    public function rejectPayment(Payment $payment): Payment
    {
        $payment->update(['status' => 'failed']);
        AuditLog::log('payment_rejected', $payment);
        $fresh = $payment->fresh(['booking']);
        if ($fresh->booking) {
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

    protected function assertUnitAvailable(AccommodationUnit $unit, $checkIn, $checkOut, ?int $excludeId = null): void
    {
        $conflict = Booking::getConflictingBooking($unit->id, $checkIn, $checkOut, $excludeId);
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
