<?php

// ============================================================
// Booking.php — Eloquent Model for the 'bookings' Table
// ============================================================
// Represents a guest's reservation for a resort package.
// Contains booking reference, dates, guest count, status,
// and all relationships to user, package, payment, and reviews.
// ============================================================

namespace App\Models;

// Import the Fillable attribute to declare mass-assignable columns
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seeder factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import the base Eloquent Model class
use Illuminate\Database\Eloquent\Model;

// Declare which columns can be mass-assigned via create() or fill()
// This is a security measure to prevent unwanted field injection
#[Fillable([
    'reference_no',       // Unique booking reference (e.g., TBR-A1B2C3D4)
    'user_id',            // Foreign key to the users table (the guest)
    'package_id',         // Foreign key to the packages table
    'booking_date',       // The date the guest will visit
    'time_slot',          // Optional time slot string (e.g., '08:00 AM - 05:00 PM')
    'guests_count',       // Number of guests included in this booking
    'status',             // Current status: pending|paid|checked_in|checked_out|cancelled|completed
    'special_requests',   // Optional text for special guest requests
    'cancelled_at',       // Timestamp when this booking was cancelled (null if not cancelled)
    'cancellation_reason',// Reason given for cancellation (required when cancelling)
    'total_amount',       // Total price = package price × guests_count
])]

/**
 * Class Booking
 *
 * Represents a single resort reservation made by a tourist.
 *
 * Status flow: pending → paid → checked_in → checked_out → completed
 *                     └→ cancelled (at any point before check-in)
 *
 * @property int         $id                  Auto-incrementing primary key
 * @property string      $reference_no        Unique booking reference code (TBR-XXXXXXXX)
 * @property int         $user_id             FK — the tourist who made the booking
 * @property int         $package_id          FK — the resort package booked
 * @property \Carbon\Carbon $booking_date     The visit date (cast to Carbon)
 * @property string|null $time_slot           Optional time range string
 * @property int         $guests_count        Number of guests
 * @property string      $status              Current lifecycle status
 * @property string|null $special_requests    Guest's special notes
 * @property \Carbon\Carbon|null $cancelled_at Cancellation timestamp
 * @property string|null $cancellation_reason Reason for cancellation
 * @property float       $total_amount        Total booking cost in PHP
 */
class Booking extends Model
{
    // Enable model factory support for seeding and testing
    use HasFactory;

    /**
     * Define how specific columns should be cast when read from the database.
     * This allows Carbon date objects, proper decimal handling, etc.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast booking_date column to a Carbon date object
            // Allows: $booking->booking_date->format('M d, Y')
            'booking_date'  => 'date',

            // Cast cancelled_at to a full Carbon datetime object (date + time)
            // Allows: $booking->cancelled_at->diffForHumans()
            'cancelled_at'  => 'datetime',

            // Cast total_amount to a decimal with 2 places (for accurate money math)
            'total_amount'  => 'decimal:2',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The tourist/guest who made this booking (many-to-one).
     * Foreign key: bookings.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Each booking belongs to exactly one user (the guest)
        return $this->belongsTo(User::class);
    }

    /**
     * The resort package that was booked (many-to-one).
     * Foreign key: bookings.package_id → packages.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function package()
    {
        // Each booking belongs to exactly one package (e.g., Day Tour, Cabin Suite)
        return $this->belongsTo(Package::class);
    }

    /**
     * The payment record associated with this booking (one-to-one).
     * A booking has at most one payment record.
     * Foreign key: payments.booking_id → bookings.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function payment()
    {
        // A booking has one payment entry (pending, success, failed, or refunded)
        return $this->hasOne(Payment::class);
    }

    /**
     * The compiled memory timeline generated after the stay (one-to-one).
     * Foreign key: memory_timelines.booking_id → bookings.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function memoryTimeline()
    {
        // A booking can optionally have one generated memory timeline PDF
        return $this->hasOne(MemoryTimeline::class);
    }

    /**
     * Individual media items (photos, videos, notes) uploaded during this booking.
     * Foreign key: memory_timeline_items.booking_id → bookings.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memoryTimelineItems()
    {
        // A booking can have many individual memory items (photos, notes, etc.)
        return $this->hasMany(MemoryTimelineItem::class);
    }

    /**
     * The guest's post-visit review for this booking (one-to-one).
     * Foreign key: reviews.booking_id → bookings.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function review()
    {
        // A booking can have at most one review submitted after the stay
        return $this->hasOne(Review::class);
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // Scopes are reusable query constraints prefixed with 'scope'.
    // Usage: Booking::pending()->get()
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter bookings with 'pending' status.
     * Pending means the booking was created but payment hasn't been confirmed.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePending($query)
    {
        // Add a WHERE clause to filter only bookings with status = 'pending'
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Filter bookings with 'paid' status.
     * Paid means payment was confirmed; guest hasn't checked in yet.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePaid($query)
    {
        // Add a WHERE clause to filter only confirmed/paid bookings
        return $query->where('status', 'paid');
    }

    /**
     * Scope: Filter active bookings (pending, paid, or checked in).
     * Active means the booking is still "alive" and occupies capacity.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        // Filter for any status that is not yet completed or cancelled
        return $query->whereIn('status', ['pending', 'paid', 'checked_in']);
    }

    // ──────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Generate a unique booking reference number in the format: TBR-XXXXXXXX
     * This is called when creating a new booking to assign a human-readable ID.
     *
     * @return string A unique 12-character reference string (e.g., TBR-A1B2C3D4)
     */
    public static function generateReferenceNo(): string
    {
        // Generate a cryptographically-random unique ID using mt_rand + uniqid,
        // then md5-hash it and take the first 8 characters, uppercased
        // Prefix with 'TBR-' to identify it as a Talisay Beach Resort reference
        return 'TBR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}
