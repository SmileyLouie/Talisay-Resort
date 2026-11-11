<?php

namespace App\Support;

use App\Models\SystemSetting;
use Carbon\Carbon;

/**
 * Booking rules managed under System Settings. Every reader of these
 * settings goes through here so the defaults match the settings form.
 */
class BookingRules
{
    public static function downPaymentPercent(): int
    {
        return max(0, min(100, (int) SystemSetting::get('down_payment_percent', 50)));
    }

    /** Down payment is offered only for a percent strictly between 0 and 100. */
    public static function downPaymentEnabled(): bool
    {
        $percent = self::downPaymentPercent();

        return $percent > 0 && $percent < 100;
    }

    public static function downPaymentDue(float $total, ?int $percent = null): float
    {
        $percent ??= self::downPaymentPercent();

        return round($total * max(0, min(100, $percent)) / 100, 2);
    }

    /** Minutes an unpaid online booking holds its room. 0 turns the release off. */
    public static function holdMinutes(): int
    {
        return max(0, (int) SystemSetting::get('hold_minutes', 120));
    }

    /** Minutes after check-in time before an unpaid booking counts as a no-show. 0 turns it off. */
    public static function noShowGraceMinutes(): int
    {
        return max(0, (int) SystemSetting::get('noshow_grace_minutes', 60));
    }

    public static function minNights(): int
    {
        return max(1, (int) SystemSetting::get('min_nights', 1));
    }

    public static function hourlyStaysEnabled(): bool
    {
        return (string) SystemSetting::get('hourly_stays_enabled', '1') !== '0';
    }

    public static function checkInTime(): string
    {
        return self::clock(SystemSetting::get('check_in_time'), '14:00');
    }

    public static function checkOutTime(): string
    {
        return self::clock(SystemSetting::get('check_out_time'), '12:00');
    }

    public static function defaultTimeSlot(): string
    {
        return self::display(self::checkInTime()) . ' Check-in · ' . self::display(self::checkOutTime()) . ' Check-out';
    }

    public static function display(string $clock): string
    {
        return Carbon::createFromFormat('H:i', $clock)->format('g:i A');
    }

    public static function arrivalRemindersEnabled(): bool
    {
        return (string) SystemSetting::get('precheckin_links', '0') === '1';
    }

    public static function arrivalReminderDays(): int
    {
        return max(0, min(30, (int) SystemSetting::get('precheckin_days', 2)));
    }

    public static function accessInstructions(): string
    {
        return trim((string) SystemSetting::get('access_instructions', ''));
    }

    public static function retentionMonths(): int
    {
        return max(1, (int) SystemSetting::get('data_retention_months', 24));
    }

    public static function notifyNewBooking(): bool
    {
        return (string) SystemSetting::get('notify_new_booking', '1') !== '0';
    }

    public static function notifyPaymentProof(): bool
    {
        return (string) SystemSetting::get('notify_payment_proof', '1') !== '0';
    }

    private static function clock(mixed $value, string $fallback): string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $value) ? $value : $fallback;
    }
}
