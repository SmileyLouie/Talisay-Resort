<?php

// ============================================================
// Package.php — Eloquent Model for the 'packages' Table
// ============================================================
// Represents a bookable resort package (e.g., Day Tour, Cabin Suite).
// Contains pricing, capacity, images, and seasonal pricing rules.
// ============================================================

namespace App\Models;

// Import Fillable attribute for declaring mass-assignable fields
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for seeder and test factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import the base Eloquent Model class
use Illuminate\Database\Eloquent\Model;

// Declare all columns that can be mass-assigned safely
#[Fillable([
    'name',             // Package display name (e.g., 'Day Tour Package')
    'description',      // Detailed description of what's included in the package
    'price',            // Base price per person in Philippine Pesos
    'max_capacity',     // Maximum number of guests allowed per booking
    'images',           // JSON array of image paths stored in public storage
    'is_visible',       // Whether this package is publicly visible for booking
    'seasonal_pricing', // JSON array of seasonal markup/discount rules
])]

/**
 * Class Package
 *
 * Represents a bookable resort package offered at Talisay Beach Resort.
 *
 * @property int         $id               Auto-incrementing primary key
 * @property string      $name             Display name of the package
 * @property string      $description      Full description of inclusions
 * @property float       $price            Base price per person (PHP)
 * @property int         $max_capacity     Maximum guests per booking
 * @property array|null  $images           Array of storage paths to package images
 * @property bool        $is_visible       Whether the package is available to book
 * @property array|null  $seasonal_pricing Array of seasonal pricing adjustment rules
 */
class Package extends Model
{
    // Enable factory support for generating test/seed data
    use HasFactory;

    /**
     * Define column type casts for automatic transformation.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast price to a 2-decimal-place float for accurate money calculations
            'price'            => 'decimal:2',

            // Cast images from a JSON string in the DB to a PHP array automatically
            // Allows: $package->images[0] to access the first image path
            'images'           => 'array',

            // Cast is_visible from integer (0/1) to proper boolean (true/false)
            'is_visible'       => 'boolean',

            // Cast seasonal_pricing from JSON string to PHP associative array
            // Allows: $package->seasonal_pricing[0]['markup_percent']
            'seasonal_pricing' => 'array',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * A package has many schedule slots defining available days/times.
     * Foreign key: package_schedules.package_id → packages.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function schedules()
    {
        // Return all schedule entries for this package (e.g., Mon-Fri 8AM-5PM)
        return $this->hasMany(PackageSchedule::class);
    }

    /**
     * A package can have many bookings over time.
     * Foreign key: bookings.package_id → packages.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        // Return all bookings that selected this package
        return $this->hasMany(Booking::class);
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter to only publicly visible packages.
     * Use this when listing packages for guests/tourists.
     * Hidden packages (is_visible = false) won't appear in booking flow.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeVisible($query)
    {
        // Add WHERE is_visible = true to only show active/published packages
        return $query->where('is_visible', true);
    }
}
