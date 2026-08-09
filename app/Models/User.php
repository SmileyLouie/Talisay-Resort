<?php

// ============================================================
// User.php — Eloquent Model for the 'users' Table
// ============================================================
// Represents a system user (admin, staff, or tourist).
// Uses Laravel Sanctum for API token authentication,
// HasFactory for database seeding/testing,
// and Notifiable for email/push notifications.
// ============================================================

namespace App\Models;

// Import the UserFactory used in tests and seeders
use Database\Factories\UserFactory;

// Import the Fillable attribute for declaring mass-assignable fields
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import the Hidden attribute for hiding sensitive fields from JSON output
use Illuminate\Database\Eloquent\Attributes\Hidden;

// Import the HasFactory trait for model factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import the base Authenticatable class (extends Model with auth features)
use Illuminate\Foundation\Auth\User as Authenticatable;

// Import the Notifiable trait for sending email/push notifications
use Illuminate\Notifications\Notifiable;

// Import Sanctum's HasApiTokens trait for API token authentication
use Laravel\Sanctum\HasApiTokens;

// Declare which fields are allowed to be mass-assigned (for create/update)
// This prevents mass-assignment vulnerabilities
#[Fillable(['name', 'email', 'phone', 'password', 'role', 'avatar', 'is_active', 'fcm_token'])]

// Hide sensitive fields from JSON serialization (API responses)
#[Hidden(['password', 'remember_token'])]

/**
 * Class User
 *
 * Represents a registered user of the Talisay Smart Tourism system.
 *
 * @property int         $id              Auto-incrementing primary key
 * @property string      $name            Full display name of the user
 * @property string      $email           Unique email address used for login
 * @property string|null $phone           Optional contact phone number
 * @property string      $password        Bcrypt-hashed password (never returned in API)
 * @property string      $role            Role: 'admin', 'staff', or 'tourist'
 * @property string|null $avatar          Storage path to the user's profile photo
 * @property bool        $is_active       Whether the account is active and can login
 * @property string|null $fcm_token       Firebase Cloud Messaging token for push notifications
 * @property string|null $email_verified_at Timestamp when email was verified
 */
class User extends Authenticatable
{
    // HasApiTokens — provides createToken(), tokens() relationship for Sanctum
    // HasFactory  — enables User::factory() for tests and seeders
    // Notifiable  — enables $user->notify() for sending notifications
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Define type casts for model attributes.
     * Casts tell Eloquent how to automatically transform column values
     * when reading from or writing to the database.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast email_verified_at to a Carbon datetime object for easy date manipulation
            'email_verified_at' => 'datetime',

            // 'hashed' cast automatically bcrypt-hashes the password when set
            // This means you never need to manually call Hash::make() in most contexts
            'password' => 'hashed',

            // Cast is_active to a boolean (true/false) instead of integer 0/1
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // Each method below defines a relationship to another model.
    // Laravel uses these to build JOIN-free eager loading queries.
    // ──────────────────────────────────────────────────────────

    /**
     * A user can have many bookings (one-to-many).
     * Foreign key: bookings.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function bookings()
    {
        // Return all bookings where user_id matches this user's id
        return $this->hasMany(Booking::class);
    }

    /**
     * A user can report many emergencies (one-to-many).
     * Foreign key: emergencies.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function emergencies()
    {
        // Return all emergencies created by this user
        return $this->hasMany(Emergency::class);
    }

    /**
     * A user (staff) can be assigned to respond to many emergencies.
     * Uses a different foreign key: emergencies.responder_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function respondedEmergencies()
    {
        // Use 'responder_id' as the foreign key instead of the default 'user_id'
        return $this->hasMany(Emergency::class, 'responder_id');
    }

    /**
     * A user can write many reviews after their stay.
     * Foreign key: reviews.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function reviews()
    {
        // Return all reviews authored by this user
        return $this->hasMany(Review::class);
    }

    /**
     * A user can have many individual memory timeline items (photos, notes, videos).
     * Foreign key: memory_timeline_items.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memoryTimelineItems()
    {
        // Return all media items uploaded by this user for any booking
        return $this->hasMany(MemoryTimelineItem::class);
    }

    /**
     * A user can have many generated memory timeline PDFs.
     * Foreign key: memory_timelines.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function memoryTimelines()
    {
        // Return all memory timeline compilations for this user
        return $this->hasMany(MemoryTimeline::class);
    }

    /**
     * A user can have many chatbot conversation logs.
     * Foreign key: chatbot_logs.user_id → users.id
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chatbotLogs()
    {
        // Return all chatbot messages/responses for this user's sessions
        return $this->hasMany(ChatbotLog::class);
    }

    /**
     * A user can receive many in-app notifications.
     * Foreign key: notifications.user_id → users.id
     * Note: Uses NotificationModel (custom) to avoid conflict with Laravel's built-in Notification
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function customNotifications()
    {
        // Return all custom notifications stored for this user
        return $this->hasMany(NotificationModel::class);
    }

    // ──────────────────────────────────────────────────────────
    // ROLE HELPER METHODS
    // Convenient boolean checks for the user's role.
    // Use these instead of checking $user->role === 'admin' directly.
    // ──────────────────────────────────────────────────────────

    /**
     * Check if this user has the 'admin' role.
     * Admins have full access to all system features.
     *
     * @return bool True if the user is an admin
     */
    public function isAdmin(): bool
    {
        // Compare the role column value to the string 'admin'
        return $this->role === 'admin';
    }

    /**
     * Check if this user has the 'staff' role.
     * Staff can manage bookings and emergencies but not system settings.
     *
     * @return bool True if the user is a staff member
     */
    public function isStaff(): bool
    {
        // Compare the role column value to the string 'staff'
        return $this->role === 'staff';
    }

    /**
     * Check if this user has the 'tourist' role.
     * Tourists are regular guests who can book, review, and view their history.
     *
     * @return bool True if the user is a tourist/guest
     */
    public function isTourist(): bool
    {
        // Compare the role column value to the string 'tourist'
        return $this->role === 'tourist';
    }

    // ──────────────────────────────────────────────────────────
    // NOTIFICATION HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Count how many in-app notifications this user has not read yet.
     * Used in the navbar to show the red badge number.
     *
     * @return int The number of unread notifications
     */
    public function unreadNotificationCount(): int
    {
        // Query the customNotifications relationship, filter for those
        // where read_at is null (meaning the user hasn't read them yet),
        // then count the results
        return $this->customNotifications()->whereNull('read_at')->count();
    }
}
