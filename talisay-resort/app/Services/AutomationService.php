<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Booking;
use App\Models\CapacitySchedule;
use App\Models\ChatbotLog;
use App\Models\NotificationModel;
use App\Models\PaymentMethod;
use App\Support\BookingRules;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Jobs that keep reservations moving without anyone at the desk. Each job
 * reads its limits from System Settings and is safe to run repeatedly.
 */
class AutomationService
{
    /** Minutes after the check-out time before a checked-in stay is closed automatically. */
    public const CHECKOUT_GRACE_MINUTES = 60;

    /** Hours after check-out before a stay is marked completed. */
    public const COMPLETE_AFTER_HOURS = 24;

    public function __construct(private BookingService $bookings)
    {
    }

    /** @return array<string, int> */
    public function runFrequent(): array
    {
        return [
            'released_unpaid' => $this->releaseUnpaidHolds(),
            'no_shows' => $this->handleNoShows(),
            'checked_out' => $this->autoCheckOut(),
            'completed' => $this->autoComplete(),
        ];
    }

    /** @return array<string, int> */
    public function runHourly(): array
    {
        return ['arrival_reminders' => $this->sendArrivalReminders()];
    }

    /** @return array<string, int> */
    public function runDaily(): array
    {
        return $this->pruneOldData() + ['capacity_days' => $this->recountCapacity()];
    }

    /**
     * Run whatever is due, at most every 5 minutes / hour / day. Lets the
     * resort stay automatic even when no scheduler process is running.
     */
    public function runIfDue(): void
    {
        foreach (['frequent' => 300, 'hourly' => 3600, 'daily' => 86400] as $group => $seconds) {
            if (! Cache::add("automation:{$group}", now()->toIso8601String(), $seconds)) {
                continue;
            }
            try {
                $method = 'run' . ucfirst($group);
                $this->{$method}();
            } catch (\Throwable $e) {
                Log::warning("Automation ({$group}) failed: " . $e->getMessage());
            }
        }
    }

    /**
     * Online bookings that still have no receipt after the hold time are
     * cancelled so the room goes back on sale. Cash-on-arrival and card
     * bookings are left alone because they are settled at the desk.
     */
    public function releaseUnpaidHolds(): int
    {
        $minutes = BookingRules::holdMinutes();
        if ($minutes === 0) {
            return 0;
        }

        $candidates = Booking::with('payment')
            ->where('status', Booking::STATUS_PENDING)
            ->where('booking_type', 'regular')
            ->where('booking_source', '!=', 'manual')
            ->where('created_at', '<', now()->subMinutes($minutes))
            ->whereHas('payment', fn ($q) => $q->where('status', 'pending')->where('is_cash_on_arrival', false)->whereNull('proof_path'))
            ->get();

        $released = 0;
        foreach ($candidates as $booking) {
            if (! PaymentMethod::needsProof($booking->payment->gateway) || $this->hasReference($booking)) {
                continue;
            }
            if ($this->safely(fn () => $this->bookings->cancel($booking, "Released automatically: no payment receipt within {$minutes} minutes.", 'booking_expired_unpaid'))) {
                $released++;
                $this->tellGuest($booking, 'Reservation Released', "We did not receive a payment receipt for {$booking->reference_no} within {$minutes} minutes, so the room was released. You are welcome to book again.");
            }
        }

        if ($released > 0) {
            NotificationModel::notifyAdminsAndStaff('booking', 'Unpaid Reservations Released', "{$released} unpaid online reservation(s) were released automatically after the {$minutes}-minute hold.");
        }

        return $released;
    }

    /**
     * After check-in time plus the grace period, an unpaid booking whose
     * guest never arrived is cancelled as a no-show. Bookings where the
     * guest already sent money are never cancelled; the desk is alerted.
     */
    public function handleNoShows(): int
    {
        $grace = BookingRules::noShowGraceMinutes();
        if ($grace === 0) {
            return 0;
        }

        $cancelled = 0;
        $bookings = Booking::with('payment')
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_PAID])
            ->where('booking_type', 'regular')
            ->whereDate('check_in_date', '<=', today())
            ->get();

        foreach ($bookings as $booking) {
            if (now()->lt($booking->arrivalAt()->addMinutes($grace))) {
                continue;
            }

            $moneySent = $booking->status === Booking::STATUS_PAID
                || ($booking->payment && ($booking->payment->status === 'success' || $booking->payment->proof_path || $this->hasReference($booking)));

            if (! $moneySent) {
                if ($this->safely(fn () => $this->bookings->cancel($booking, "No-show: the guest did not arrive within {$grace} minutes of check-in time.", 'booking_no_show'))) {
                    $cancelled++;
                    $this->tellGuest($booking, 'Reservation Marked as No-Show', "You did not arrive for {$booking->reference_no} by " . $booking->arrivalAt()->addMinutes($grace)->format('g:i A') . ', so the reservation was cancelled. Contact the resort if this is a mistake.');
                }
                continue;
            }

            if (now()->lt($booking->departureAt())) {
                if (! $this->alreadyLogged('booking_no_show_alert', $booking)) {
                    AuditLog::log('booking_no_show_alert', $booking);
                    NotificationModel::notifyAdminsAndStaff('booking', 'Guest Has Not Checked In', "{$booking->guest_name} has not checked in for {$booking->reference_no} and has already sent payment. Contact the guest or update the booking.", ['booking_id' => $booking->id]);
                }
            } elseif ($booking->status === Booking::STATUS_PAID && ! $this->alreadyLogged('booking_ended_no_checkin', $booking)) {
                AuditLog::log('booking_ended_no_checkin', $booking);
                NotificationModel::notifyAdminsAndStaff('booking', 'Paid Stay Ended Without Check-in', "{$booking->reference_no} ({$booking->guest_name}) was paid but the guest never checked in, and the stay has ended. Mark it completed if the guest stayed, or cancel it and settle the refund.", ['booking_id' => $booking->id]);
            }
        }

        return $cancelled;
    }

    /** A checked-in stay is closed once its check-out time has passed. */
    public function autoCheckOut(): int
    {
        $count = 0;
        $bookings = Booking::where('status', Booking::STATUS_CHECKED_IN)
            ->whereDate('check_in_date', '<=', today())
            ->get();

        foreach ($bookings as $booking) {
            if (now()->lt($booking->departureAt()->addMinutes(self::CHECKOUT_GRACE_MINUTES))) {
                continue;
            }
            if ($this->safely(fn () => $this->bookings->changeStatus($booking, Booking::STATUS_CHECKED_OUT, ['audit_action' => 'booking_auto_checked_out']))) {
                $count++;
                NotificationModel::notifyAdminsAndStaff('booking', 'Guest Checked Out Automatically', "{$booking->reference_no} was checked out because its check-out time passed. Collect any balance still due.", ['booking_id' => $booking->id]);
            }
        }

        return $count;
    }

    /** Checked-out stays become completed a day later, which opens reviews and closes the record. */
    public function autoComplete(): int
    {
        $count = 0;
        $bookings = Booking::where('status', Booking::STATUS_CHECKED_OUT)
            ->whereDate('check_in_date', '<=', today())
            ->get();

        foreach ($bookings as $booking) {
            if (now()->lt($booking->departureAt()->addHours(self::COMPLETE_AFTER_HOURS))) {
                continue;
            }
            if ($this->safely(fn () => $this->bookings->changeStatus($booking, Booking::STATUS_COMPLETED, ['audit_action' => 'booking_auto_completed']))) {
                $count++;
                $this->tellGuest($booking, 'Thank You for Staying', "Your stay {$booking->reference_no} is complete. We would love to hear about it in a review.");
            }
        }

        return $count;
    }

    /**
     * Guests arriving within the reminder window get one reminder with the
     * check-in time, and the access instructions once their payment is confirmed.
     */
    public function sendArrivalReminders(): int
    {
        if (! BookingRules::arrivalRemindersEnabled()) {
            return 0;
        }

        $days = BookingRules::arrivalReminderDays();
        $access = BookingRules::accessInstructions();
        $sent = 0;

        $bookings = Booking::with(['accommodationUnit', 'payment'])
            ->whereIn('status', [Booking::STATUS_PENDING, Booking::STATUS_PAID])
            ->whereNotNull('user_id')
            ->whereDate('check_in_date', '>=', today())
            ->whereDate('check_in_date', '<=', today()->addDays($days))
            ->get();

        foreach ($bookings as $booking) {
            if ($this->alreadyLogged('arrival_reminder_sent', $booking)) {
                continue;
            }

            $where = $booking->isSpecialResort() ? 'the whole resort' : ($booking->accommodationUnit->unit_number ?? 'your room');
            $message = "Your stay at {$where} ({$booking->reference_no}) starts " . ph_date($booking->arrivalAt(), 'F j, Y \a\t g:i A') . '.';
            if ($booking->status === Booking::STATUS_PAID) {
                $message .= $access !== '' ? " Arrival details: {$access}" : ' Show your reference number at the front desk.';
            } else {
                $message .= ' Your payment is not confirmed yet. Please complete it before you arrive.';
            }

            $this->tellGuest($booking, 'Your Stay Is Coming Up', $message);
            AuditLog::log('arrival_reminder_sent', $booking);
            $sent++;
        }

        return $sent;
    }

    /**
     * Removes old notifications and chatbot conversations. Booking and
     * payment records are kept for the resort's books.
     *
     * @return array{old_notifications: int, old_chat_logs: int}
     */
    public function pruneOldData(): array
    {
        $cutoff = now()->subMonths(BookingRules::retentionMonths());

        return [
            'old_notifications' => NotificationModel::where('created_at', '<', $cutoff)->delete(),
            'old_chat_logs' => ChatbotLog::where('created_at', '<', $cutoff)->delete(),
        ];
    }

    /** Rebuilds visitor counts for every upcoming day that has a count or an active booking. */
    public function recountCapacity(): int
    {
        $from = Carbon::today();
        $dates = CapacitySchedule::whereDate('date', '>=', $from)->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->format('Y-m-d'))
            ->all();

        Booking::active()->whereDate('check_out_date', '>=', $from)->get()
            ->each(function (Booking $booking) use (&$dates, $from) {
                foreach ($booking->occupiedDates() as $date) {
                    if ($date >= $from->format('Y-m-d')) {
                        $dates[] = $date;
                    }
                }
            });

        $dates = array_values(array_unique($dates));
        foreach ($dates as $date) {
            CapacitySchedule::recalculateForDate($date);
        }

        return count($dates);
    }

    private function hasReference(Booking $booking): bool
    {
        $id = (string) ($booking->payment->transaction_id ?? '');

        return $id !== '' && ! preg_match('/^(TXN|MNL|CASH)-/', $id);
    }

    private function alreadyLogged(string $action, Booking $booking): bool
    {
        return AuditLog::where('action', $action)
            ->where('model_type', Booking::class)
            ->where('model_id', $booking->id)
            ->exists();
    }

    private function tellGuest(Booking $booking, string $title, string $message): void
    {
        if ($booking->user_id) {
            NotificationModel::notifyUser($booking->user_id, 'booking', $title, $message, ['booking_id' => $booking->id, 'reference_no' => $booking->reference_no]);
        }
    }

    private function safely(callable $action): bool
    {
        try {
            $action();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Automation step skipped: ' . $e->getMessage());

            return false;
        }
    }
}
