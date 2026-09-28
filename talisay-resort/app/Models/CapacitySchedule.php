<?php

// ============================================================
// CapacitySchedule.php — Model for the 'capacity_schedules' Table
// ============================================================
// Tracks daily resort capacity — how many guests are allowed
// on a specific date and how many have already booked.
// Used to prevent overbooking and display availability calendar.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seed factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model
use Illuminate\Database\Eloquent\Model;

// Declare mass-assignable columns
#[Fillable([
    'date',          // The specific calendar date this record tracks
    'max_capacity',  // Maximum guests allowed on this date
    'current_count', // How many guests have already booked for this date
])]

/**
 * Class CapacitySchedule
 *
 * Tracks per-day guest capacity for the resort.
 * Each row represents one calendar date with a maximum guest limit
 * and the current count of guests already booked.
 *
 * @property int          $id            Auto-incrementing primary key
 * @property \Carbon\Carbon $date        The calendar date (cast to Carbon)
 * @property int          $max_capacity  Maximum allowed guests per day
 * @property int          $current_count Current number of booked guests for this day
 */
class CapacitySchedule extends Model
{
    // Enable factory support for seeder and tests
    use HasFactory;

    /**
     * Define type casts for model attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // STATIC METHODS
    // ──────────────────────────────────────────────────────────

    /**
     * Get or create the capacity record for a specific date.
     * If no record exists, one is created with the default daily cap from settings.
     * This ensures every date always has a capacity record when needed.
     *
     * @param  string|\Carbon\Carbon $date The date to look up (YYYY-MM-DD or Carbon)
     * @return static                      The CapacitySchedule record for that date
     */
    public static function getCapacityForDate($date): self
    {
        $formattedDate = $date instanceof \Carbon\CarbonInterface
            ? $date->format('Y-m-d')
            : \Carbon\Carbon::parse($date)->format('Y-m-d');

        $record = static::whereDate('date', $formattedDate)->first();

        if ($record) {
            return $record;
        }

        try {
            return static::create([
                'date'          => $formattedDate,
                'max_capacity'  => (int) setting('daily_visitor_cap', 100),
                'current_count' => 0,
            ]);
        } catch (\Illuminate\Database\UniqueConstraintViolationException) {
            // A concurrent request created the row between our lookup and insert.
            return static::whereDate('date', $formattedDate)->firstOrFail();
        }
    }

    /**
     * Rebuild the guest count for one date from the bookings that actually
     * occupy it. An approved full-resort booking locks the whole day.
     * Being derived from source data, this can never drift or go negative.
     */
    public static function recalculateForDate($date): self
    {
        $capacity = static::getCapacityForDate($date);
        $day      = $capacity->date->format('Y-m-d');

        $occupying = Booking::active()->occupyingDate($day);

        $hasApprovedSpecial = (clone $occupying)
            ->where('booking_type', 'special_resort')
            ->where('admin_approval_status', 'approved')
            ->exists();

        $count = $hasApprovedSpecial
            ? (int) $capacity->max_capacity
            : (int) (clone $occupying)->where('booking_type', 'regular')->sum('guests_count');

        if ((int) $capacity->current_count !== $count) {
            $capacity->update(['current_count' => $count]);
        }

        return $capacity;
    }

    /**
     * Recalculate every night a booking occupies ([check-in, check-out)).
     */
    public static function recalculateForBooking(Booking $booking): void
    {
        foreach ($booking->occupiedDates() as $date) {
            static::recalculateForDate($date);
        }
    }

    // ──────────────────────────────────────────────────────────
    // INSTANCE METHODS
    // ──────────────────────────────────────────────────────────

    /**
     * Calculate and return the current occupancy utilization as a percentage.
     * Used for the dashboard chart and availability calendar coloring.
     *
     * @return float Utilization percentage rounded to 1 decimal place (0.0 – 100.0)
     */
    public function getUtilizationPercent(): float
    {
        // Prevent division by zero if max_capacity is not set or zero
        if ($this->max_capacity <= 0) return 0;

        // Calculate: (current guests / max capacity) × 100, rounded to 1 decimal
        return round(($this->current_count / $this->max_capacity) * 100, 1);
    }

    /**
     * Check whether there is enough available capacity for a given number of guests.
     * Called before confirming a new booking to prevent overbooking.
     *
     * @param  int  $guests Number of guests in the incoming booking request
     * @return bool         True if there is room for the guests; false if full
     */
    public function isAvailable(int $guests = 1): bool
    {
        // Check if adding the new guests would exceed the maximum capacity
        // current_count + incoming guests must be ≤ max_capacity
        return ($this->current_count + $guests) <= $this->max_capacity;
    }
}
