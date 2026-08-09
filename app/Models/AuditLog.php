<?php

// ============================================================
// AuditLog.php — Model for the 'audit_logs' Table
// ============================================================
// Records a history of important administrative actions taken in the system.
// Examples: payment_approved, booking_status_changed, user_login, review_approved.
// Used for accountability, security auditing, and debugging.
// ============================================================

namespace App\Models;

// Import Fillable attribute for declaring mass-assignable columns
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seed factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model class
use Illuminate\Database\Eloquent\Model;

// Declare which columns are safely mass-assignable
#[Fillable([
    'user_id',    // FK — the admin or staff member who performed the action
    'action',     // A descriptive action string (e.g., 'payment_approved', 'user_login')
    'model_type', // PHP class name of the model that was modified (e.g., 'App\Models\Payment')
    'model_id',   // The primary key (ID) of the model instance that was modified
    'old_values', // JSON snapshot of the data BEFORE the change
    'new_values', // JSON snapshot of the data AFTER the change
])]

/**
 * Class AuditLog
 *
 * Tracks administrative actions for accountability and security auditing.
 * Every significant change (payment approvals, emergency updates, etc.)
 * should create an audit log entry using AuditLog::log().
 *
 * @property int         $id          Auto-incrementing primary key
 * @property int|null    $user_id     FK — who performed the action (null for system actions)
 * @property string      $action      Description of the action taken
 * @property string|null $model_type  PHP class name of the affected model
 * @property int|null    $model_id    ID of the affected model instance
 * @property array|null  $old_values  Previous state of the modified data
 * @property array|null  $new_values  New state after the modification
 */
class AuditLog extends Model
{
    // Enable factory support for test data generation
    use HasFactory;

    /**
     * Define type casts for automatic data transformation.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast old_values from JSON string to PHP array
            // Allows: $log->old_values['status'] to read the previous value
            'old_values' => 'array',

            // Cast new_values from JSON string to PHP array
            // Allows: $log->new_values['status'] to read the new value
            'new_values' => 'array',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The user who performed the logged action (many-to-one).
     * Foreign key: audit_logs.user_id → users.id
     * Can be null for system-generated actions.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Each audit log entry belongs to the user who performed the action
        return $this->belongsTo(User::class);
    }

    // ──────────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────────

    /**
     * Create a new audit log entry for an action.
     * This is the main entry point for recording audit trails throughout the app.
     * Call this after any significant admin/staff action.
     *
     * Usage examples:
     *   AuditLog::log('payment_approved', $payment, ['status' => 'pending'], ['status' => 'success']);
     *   AuditLog::log('user_login');
     *
     * @param  string       $action Action string describing what happened
     * @param  Model|null   $model  The Eloquent model instance that was changed (optional)
     * @param  array|null   $old    Snapshot of old/before values (optional)
     * @param  array|null   $new    Snapshot of new/after values (optional)
     * @return static               The newly created AuditLog instance
     */
    public static function log(string $action, ?Model $model = null, ?array $old = null, ?array $new = null): self
    {
        // Create a new audit log row in the database
        return static::create([
            // Record which user performed this action using the current auth session
            // auth()->id() returns null if no user is logged in (e.g., system jobs)
            'user_id'    => auth()->id(),

            // Store the descriptive action name for filtering and display
            'action'     => $action,

            // Get the fully-qualified class name of the affected model
            // get_class($model) returns e.g. 'App\Models\Payment'
            // null if no specific model is associated
            'model_type' => $model ? get_class($model) : null,

            // Get the primary key value of the affected model instance
            // getKey() returns the value of the primary key column (usually 'id')
            // The ?-> null-safe operator prevents error if $model is null
            'model_id'   => $model?->getKey(),

            // Store the old values array (JSON-encoded by the 'array' cast on read)
            'old_values' => $old,

            // Store the new values array (JSON-encoded by the 'array' cast on read)
            'new_values' => $new,
        ]);
    }
}
