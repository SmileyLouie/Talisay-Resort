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
    'user_id',            // FK — the guest who wrote this review
    'booking_id',         // FK — the specific booking this review is for
    'rating',             // Integer star rating from 1 (poor) to 5 (excellent)
    'comment',            // Written review text from the guest
    'is_approved',        // Whether review is active (default true)
    'is_comment_blocked', // Whether comment text is blocked due to inappropriate language / bad words
    'block_reason',       // Reason why the comment text was blocked
    'comment_blocked_at', // When the comment was blocked
    'external_id',        // UUID shared with the Supabase client review
])]

/**
 * Class Review
 *
 * Represents a guest's post-stay review for a specific booking.
 *
 * @property int      $id                 Auto-incrementing primary key
 * @property int      $user_id            FK — the reviewing guest
 * @property int      $booking_id         FK — the associated booking
 * @property int      $rating             Star rating (1–5)
 * @property string   $comment            Written review text
 * @property bool     $is_approved        Whether the review is publicly visible
 * @property bool     $is_comment_blocked Whether the comment text is blocked
 * @property string|null $block_reason    Moderation reason
 * @property \Carbon\Carbon|null $comment_blocked_at
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
            'is_approved'        => 'boolean',
            'is_comment_blocked' => 'boolean',
            'comment_blocked_at' => 'datetime',
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
    // PROFANITY & MODERATION HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * List of common offensive words / profanities in English and Tagalog/Filipino.
     */
    public static function profanityWords(): array
    {
        return [
            // Tagalog / Filipino profanities
            'putang', 'puta', 'tangina', 'tang ina', 'gago', 'gaga', 'tarantado', 'tarantada',
            'ulol', 'ogag', 'bobo', 'inutil', 'leche', 'letse', 'pakshet', 'pakyu', 'hayop ka',
            'peste', 'bwisit', 'punyeta', 'kantot', 'iyot', 'kupal', 'hudas',
            // English profanities
            'fuck', 'fucking', 'fucker', 'shit', 'shitty', 'bullshit', 'bitch', 'asshole',
            'bastard', 'cunt', 'dick', 'pussy', 'cock', 'motherfucker', 'whore', 'slut',
            'dumbass', 'jackass', 'retard', 'nigger', 'faggot',
        ];
    }

    /**
     * Check if a text contains offensive / bad words.
     */
    public static function containsProfanity(?string $text): bool
    {
        if (empty($text)) {
            return false;
        }

        $lower = strtolower($text);

        foreach (self::profanityWords() as $badWord) {
            $pattern = '/\b' . preg_quote($badWord, '/') . '\b/i';
            if (preg_match($pattern, $lower)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Return sanitized/masked comment for public view.
     * Star rating is always retained, but if comment is blocked,
     * this returns a respectful placeholder.
     */
    public function getDisplayCommentAttribute(): string
    {
        if ($this->is_comment_blocked) {
            return '[This comment has been blocked by the admin for inappropriate language. Star rating preserved.]';
        }

        return $this->comment ?? '';
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter to active reviews (all reviews are active by default).
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope: Filter reviews with unblocked comments.
     */
    public function scopeCommentActive($query)
    {
        return $query->where('is_comment_blocked', false);
    }

    /**
     * Scope: Filter reviews with blocked comments.
     */
    public function scopeCommentBlocked($query)
    {
        return $query->where('is_comment_blocked', true);
    }
}
