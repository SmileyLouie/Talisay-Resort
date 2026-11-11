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

    /**
     * Validation rules for a submitted settings[...] array. Known keys get their
     * own type and range; any other key must be a short plain value.
     */
    public static function validationRules(): array
    {
        $flag = 'nullable|in:0,1';

        return [
            'settings'                         => 'required|array|max:100',
            'settings.*'                       => ['nullable', function ($attribute, $value, $fail) {
                $key = substr($attribute, strlen('settings.'));
                if (! preg_match('/^[a-z0-9_]{1,64}$/', $key)) {
                    $fail('That setting name is not allowed.');
                } elseif (is_array($value)) {
                    $fail('Each setting must be a single value.');
                } elseif (mb_strlen((string) $value) > 5000) {
                    $fail('A setting can be at most 5000 characters.');
                }
            }],
            'settings.resort_name'             => 'nullable|string|max:120',
            'settings.resort_address'          => 'nullable|string|max:255',
            'settings.contact_phone'           => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{7,30}$/'],
            'settings.contact_email'           => 'nullable|email|max:120',
            'settings.ui_locale'               => 'nullable|string|max:10',
            'settings.check_in_time'           => 'nullable|date_format:H:i',
            'settings.check_out_time'          => 'nullable|date_format:H:i',
            'settings.precheckin_links'        => $flag,
            'settings.precheckin_required'     => $flag,
            'settings.precheckin_days'         => 'nullable|integer|min:0|max:30',
            'settings.access_instructions'     => 'nullable|string|max:2000',
            'settings.hold_minutes'            => 'nullable|integer|min:0|max:10080',
            'settings.noshow_grace_minutes'    => 'nullable|integer|min:0|max:1440',
            'settings.hourly_stays_enabled'    => $flag,
            'settings.min_nights'              => 'nullable|integer|min:1|max:30',
            'settings.down_payment_percent'    => 'nullable|numeric|min:0|max:100',
            'settings.data_retention_months'   => 'nullable|integer|min:1|max:120',
            'settings.guest_data_export'       => $flag,
            'settings.notify_new_booking'      => $flag,
            'settings.notify_payment_proof'    => $flag,
            'settings.daily_visitor_cap'       => 'nullable|integer|min:1|max:10000',
            'settings.exclusive_title'         => 'nullable|string|max:120',
            'settings.exclusive_description'   => 'nullable|string|max:3000',
            'settings.exclusive_information'   => 'nullable|string|max:3000',
            'settings.exclusive_nightly_rate'  => 'nullable|numeric|min:0|max:10000000',
            'settings.exclusive_occasions'     => 'nullable|string|max:3000',
            'settings.stripe_key'              => 'nullable|string|max:255',
            'settings.stripe_secret'           => 'nullable|string|max:255',
            'settings.stripe_webhook_secret'   => 'nullable|string|max:255',
        ];
    }

    public static function validationMessages(): array
    {
        return [
            'settings.contact_phone.regex'         => 'Enter a valid contact number.',
            'settings.check_in_time.date_format'   => 'Check-in time must look like 14:00.',
            'settings.check_out_time.date_format'  => 'Check-out time must look like 12:00.',
            'settings.down_payment_percent.max'    => 'Down payment can be at most 100%.',
        ];
    }
}
