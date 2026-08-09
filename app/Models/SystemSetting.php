<?php

// ============================================================
// SystemSetting.php — Model for the 'system_settings' Table
// ============================================================
// Stores configurable key-value pairs for the resort system.
// Examples: daily_visitor_cap, cancellation_window_hours, check_in_time.
// Provides static get/set methods used by the setting() helper function.
// ============================================================

namespace App\Models;

// Import Fillable attribute for mass-assignment protection
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for test/seed factory support
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import base Eloquent Model
use Illuminate\Database\Eloquent\Model;

// Only 'key' and 'value' are mass-assignable in this simple key-value model
#[Fillable(['key', 'value'])]

/**
 * Class SystemSetting
 *
 * Stores dynamic system configuration as key-value pairs.
 * Settings are managed by admin through the Settings page.
 * The global setting() helper function delegates to this model.
 *
 * @property int    $id    Auto-incrementing primary key
 * @property string $key   Unique setting identifier (e.g., 'daily_visitor_cap')
 * @property string $value The stored value as a string (cast as needed at call site)
 */
class SystemSetting extends Model
{
    // Enable factory support for tests
    use HasFactory;

    // ──────────────────────────────────────────────────────────
    // STATIC METHODS
    // ──────────────────────────────────────────────────────────

    /**
     * Retrieve a setting value by its key from the database.
     * Returns $default if no matching setting is found.
     * This is the method called by the global setting() helper.
     *
     * @param  string $key     The setting key to look up (e.g., 'daily_visitor_cap')
     * @param  mixed  $default Fallback value if the key doesn't exist in the database
     * @return mixed           The stored setting value, or the default
     */
    public static function get(string $key, $default = null): mixed
    {
        // Query the database for the first row where key matches
        $setting = static::where('key', $key)->first();

        // If a matching row was found, return its value; otherwise return the default
        return $setting ? $setting->value : $default;
    }

    /**
     * Create or update a setting by its key.
     * If the key already exists, update its value. If not, create a new row.
     * Used by the Settings admin page to persist changes.
     *
     * @param  string $key   The setting key to create or update
     * @param  mixed  $value The new value to store (will be cast to string in DB)
     * @return static        The resulting SystemSetting model instance
     */
    public static function set(string $key, $value): self
    {
        // updateOrCreate: find by 'key', update 'value' or create new if not found
        return static::updateOrCreate(
            // Search condition: find the row with this key
            ['key' => $key],

            // Update or insert this value
            ['value' => $value]
        );
    }
}
