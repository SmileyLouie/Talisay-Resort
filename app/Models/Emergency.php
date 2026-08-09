<?php

// ============================================================
// Emergency.php — Eloquent Model for the 'emergencies' Table
// ============================================================
// Tracks emergency incidents reported by guests on-site.
// Categories: medical, lost_item, security, other.
// Status flow: pending → acknowledged → responding → resolved
// Each emergency can be assigned a staff responder.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test and seeder factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model class
use Illuminate\Database\Eloquent\Model;

// Declare all columns that are safe to mass-assign
#[Fillable([
    'user_id',         // FK — the guest who reported the emergency
    'category',        // Type of emergency: medical|lost_item|security|other
    'description',     // Guest's description of the emergency situation
    'photo_path',      // Optional storage path to a photo of the incident
    'latitude',        // GPS latitude of the incident location
    'longitude',       // GPS longitude of the incident location
    'status',          // Lifecycle status: pending|acknowledged|responding|resolved
    'tracking_number', // Unique EMR-XXXXXXXX reference for the guest to track
    'resolved_at',     // Timestamp when the emergency was fully resolved
    'responder_id',    // FK — the staff member assigned to respond
    'response_notes',  // Staff's notes about the response and resolution
])]

/**
 * Class Emergency
 *
 * Represents an emergency incident reported at the resort.
 *
 * @property int          $id              Auto-incrementing primary key
 * @property int          $user_id         FK — reporting guest
 * @property string       $category        medical|lost_item|security|other
 * @property string       $description     Description of the incident
 * @property string|null  $photo_path      Path to incident photo
 * @property float|null   $latitude        GPS latitude coordinate
 * @property float|null   $longitude       GPS longitude coordinate
 * @property string       $status          Current response status
 * @property string       $tracking_number Unique EMR reference (e.g., EMR-A1B2C3D4)
 * @property \Carbon\Carbon|null $resolved_at Resolution timestamp
 * @property int|null     $responder_id    FK — assigned staff member
 * @property string|null  $response_notes  Staff notes about resolution
 */
class Emergency extends Model
{
    // Enable factory support for seeder and unit tests
    use HasFactory;

    /**
     * Define type casts for model attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast latitude to a decimal with 7 places for GPS precision
            'latitude'    => 'decimal:7',

            // Cast longitude to a decimal with 7 places for GPS precision
            'longitude'   => 'decimal:7',

            // Cast resolved_at to a full Carbon datetime object
            // Allows: $emergency->resolved_at->format('M d, Y H:i')
            'resolved_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The guest who reported this emergency (many-to-one).
     * Foreign key: emergencies.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Each emergency was reported by one guest user
        return $this->belongsTo(User::class);
    }

    /**
     * The staff member assigned to respond to this emergency (many-to-one).
     * Uses 'responder_id' as the foreign key instead of default 'user_id'.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function responder()
    {
        // Point to the users table using the custom foreign key 'responder_id'
        return $this->belongsTo(User::class, 'responder_id');
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter emergencies that have NOT been resolved yet.
     * Used to count active emergencies shown in the dashboard badge.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUnresolved($query)
    {
        // Exclude emergencies with 'resolved' status
        // This includes: pending, acknowledged, and responding
        return $query->whereNotIn('status', ['resolved']);
    }

    // ──────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Generate a unique emergency tracking number in the format: EMR-XXXXXXXX
     * Assigned when a new emergency is created so guests can track its status.
     *
     * @return string A unique 12-character tracking number (e.g., EMR-A1B2C3D4)
     */
    public static function generateTrackingNumber(): string
    {
        // Use the same approach as booking references:
        // combine mt_rand with uniqid, md5-hash the result, take first 8 chars uppercase
        return 'EMR-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 8));
    }
}
