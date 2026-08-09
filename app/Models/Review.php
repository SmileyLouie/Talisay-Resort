<?php

// ============================================================
// Review.php — Eloquent Model for the 'reviews' Table
// ============================================================
// Represents a post-visit guest review submitted after a completed booking.
// Reviews have a 1-5 star rating and a written comment.
// Admin must approve reviews before they appear publicly.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test and seeder factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model class
use Illuminate\Database\Eloquent\Model;

// Declare which columns can be safely mass-assigned
#[Fillable([
    'user_id',     // FK — the guest who wrote this review
    'booking_id',  // FK — the specific booking this review is for
    'rating',      // Integer star rating from 1 (poor) to 5 (excellent)
    'comment',     // Written review text from the guest
    'is_approved', // Whether an admin has approved this review for public display
])]

/**
 * Class Review
 *
 * Represents a guest's post-stay review for a specific booking.
 *
 * @property int      $id          Auto-incrementing primary key
 * @property int      $user_id     FK — the reviewing guest
 * @property int      $booking_id  FK — the associated booking
 * @property int      $rating      Star rating (1–5)
 * @property string   $comment     Written review text
 * @property bool     $is_approved Whether the review is publicly visible
 */
class Review extends Model
{
    // Enable factory support for generating test/seed reviews
    use HasFactory;

    /**
     * Define type casts for model attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast is_approved from integer (0/1) to boolean (true/false)
            // Allows: if ($review->is_approved) { ... }
            'is_approved' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The guest who wrote this review (many-to-one).
     * Foreign key: reviews.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Each review belongs to one guest who submitted it
        return $this->belongsTo(User::class);
    }

    /**
     * The booking this review refers to (many-to-one).
     * Foreign key: reviews.booking_id → bookings.id
     * A guest can only review a booking they actually completed.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking()
    {
        // Each review is tied to a single completed booking
        return $this->belongsTo(Booking::class);
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter to only admin-approved reviews.
     * Use this when displaying public reviews on the front-end.
     * Unapproved reviews are only visible to admins for moderation.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        // Add WHERE is_approved = true to filter only publicly-visible reviews
        return $query->where('is_approved', true);
    }
}
