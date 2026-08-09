<?php

// ============================================================
// NotificationModel.php — Model for the 'notifications_table' Table
// ============================================================
// Stores in-app notifications for users (admins, staff, and tourists).
// Types: booking, payment, emergency, review, system.
// Supports read/unread state with a read_at timestamp.
// Named 'NotificationModel' to avoid conflict with Laravel's built-in
// Notification facade and Notifiable trait.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seed factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model
use Illuminate\Database\Eloquent\Model;

// Declare mass-assignable columns for this model
#[Fillable([
    'user_id', // FK — the user who should receive this notification
    'type',    // Category: 'booking'|'payment'|'emergency'|'review'|'system'
    'title',   // Short notification headline (e.g., 'New Booking Received')
    'message', // Full notification body text
    'data',    // JSON blob for extra data (e.g., booking_id, payment_id for deep links)
    'read_at', // Timestamp when the user read/dismissed this notification (null = unread)
])]

/**
 * Class NotificationModel
 *
 * Represents an in-app notification delivered to a specific user.
 * Used by the navbar notification bell and mobile app push notifications.
 *
 * @property int          $id       Auto-incrementing primary key
 * @property int          $user_id  FK — the recipient user
 * @property string       $type     Notification category/type
 * @property string       $title    Short headline text
 * @property string       $message  Full message body
 * @property array|null   $data     Extra structured data (links, IDs, etc.)
 * @property \Carbon\Carbon|null $read_at Timestamp when marked as read
 */
class NotificationModel extends Model
{
    // Enable factory support for generating test notifications
    use HasFactory;

    /**
     * Override the default table name.
     * Laravel would guess 'notification_models', but the actual table is 'notifications_table'.
     *
     * @var string
     */
    protected $table = 'notifications_table';

    /**
     * Define type casts for automatic data transformation.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast data JSON string to PHP array
            // Allows: $notif->data['booking_id'] to access linked booking
            'data'    => 'array',

            // Cast read_at to a Carbon datetime object
            // Allows: $notif->read_at->diffForHumans() to show relative time
            'read_at' => 'datetime',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The user who receives this notification (many-to-one).
     * Foreign key: notifications_table.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Each notification belongs to exactly one recipient user
        return $this->belongsTo(User::class);
    }

    // ──────────────────────────────────────────────────────────
    // INSTANCE METHODS
    // ──────────────────────────────────────────────────────────

    /**
     * Check whether this notification has been read by the user.
     * A notification is considered read if read_at is not null.
     *
     * @return bool True if the notification has been read
     */
    public function isRead(): bool
    {
        // If read_at has a value (not null), the notification has been read
        return $this->read_at !== null;
    }

    /**
     * Mark this notification as read by setting the read_at timestamp.
     * Only updates if the notification hasn't been read yet (idempotent).
     *
     * @return void
     */
    public function markAsRead(): void
    {
        // Only update if read_at is currently null (not yet read)
        // This prevents unnecessary database writes for already-read notifications
        if (is_null($this->read_at)) {
            // Set read_at to the current timestamp to mark it as read
            $this->update(['read_at' => now()]);
        }
    }
}
