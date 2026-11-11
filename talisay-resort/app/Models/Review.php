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
use App\Support\ProfanityFilter;

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

    public const AUTO_BLOCK_REASON = 'Automatically blocked: offensive language detected';

    protected static function booted(): void
    {
        static::saving(function (Review $review) {
            if (!$review->isDirty('comment') || !self::containsProfanity($review->comment)) {
                return;
            }

            $review->is_comment_blocked = true;
            $review->block_reason = self::AUTO_BLOCK_REASON;
            $review->comment_blocked_at ??= now();
        });
    }

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
        // Each review is tied to a single completed booking.
        // A booking removed from the list still shows on its review.
        return $this->belongsTo(Booking::class)->withTrashed();
    }

    // ──────────────────────────────────────────────────────────
    // PROFANITY & MODERATION HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Offensive words the automatic check looks for, in the languages the site can show.
     */
    public static function profanityWords(): array
    {
        return ProfanityFilter::words();
    }

    /**
     * Check if a text contains offensive words in any supported language.
     */
    public static function containsProfanity(?string $text): bool
    {
        return ProfanityFilter::contains($text);
    }

    /**
     * Fields to save when a new comment is stored.
     * A clean comment stays visible. An offensive one is hidden immediately.
     *
     * @return array{is_comment_blocked: bool, block_reason: string|null, comment_blocked_at: \Carbon\Carbon|null}
     */
    public static function blockedAttributes(?string $comment): array
    {
        $blocked = self::containsProfanity($comment);

        return [
            'is_comment_blocked' => $blocked,
            'block_reason' => $blocked ? self::AUTO_BLOCK_REASON : null,
            'comment_blocked_at' => $blocked ? now() : null,
        ];
    }

    /**
     * Hide older comments that were saved before this check, or that a new word now catches.
     * A comment an admin has already unblocked is left alone until the text changes.
     */
    public static function sweepOpenComments(): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;

        self::query()
            ->where('is_comment_blocked', false)
            ->where(function ($query) {
                $query->whereNull('block_reason')->orWhere('block_reason', '');
            })
            ->whereNotNull('comment')
            ->where('comment', '!=', '')
            ->select(['id', 'comment'])
            ->orderBy('id')
            ->chunkById(100, function ($reviews) {
                foreach ($reviews as $review) {
                    if (!self::containsProfanity($review->comment)) {
                        continue;
                    }

                    self::whereKey($review->id)->update([
                        'is_comment_blocked' => true,
                        'block_reason' => self::AUTO_BLOCK_REASON,
                        'comment_blocked_at' => now(),
                    ]);
                }
            });
    }

    /**
     * Return sanitized/masked comment for public view.
     * Star rating is always retained, but if comment is blocked,
     * this returns a respectful placeholder.
     */
    public function getDisplayCommentAttribute(): string
    {
        if ($this->is_comment_blocked) {
            return '[This comment was hidden automatically because it contains offensive language. The star rating is still counted.]';
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
