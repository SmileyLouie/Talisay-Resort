<?php

// ============================================================
// PackageSchedule.php — Eloquent Model for 'package_schedules'
// ============================================================
// Defines recurring availability for packages based on the day
// of the week, timing, and initial capacity slots.
// ============================================================

namespace App\Models;

// Import the Fillable attribute for declaring mass-assignable model attributes
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for factory/seeding support in database operations
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import the base Eloquent Model class from the framework
use Illuminate\Database\Eloquent\Model;

// Declare the attributes that are mass-assignable via model creation methods
#[Fillable([
    'package_id',      // Foreign key to the associated packages table
    'day_of_week',     // Day of week ('monday', 'tuesday', etc.) when package is active
    'start_time',      // The scheduled start/check-in time for bookings
    'end_time',        // The scheduled end/check-out time for bookings
    'available_slots', // The maximum slots allocated to this package on this day
])]
class PackageSchedule extends Model
{
    // Apply the HasFactory trait to enable database seeding and factory creation
    use HasFactory;

    /**
     * Define the relationship to the Package model (a schedule belongs to a package).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        // Return package relationship: a schedule belongs to one package
        return $this->belongsTo(Package::class);
    }
}
