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

        return static::create([
            'date'          => $formattedDate,
            'max_capacity'  => setting('daily_visitor_cap', 100),
            'current_count' => 0,
        ]);
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
