<?php

// ============================================================
// helpers.php — Global Helper Functions for Talisay Smart Tourism
// These functions are auto-loaded by Composer (see composer.json)
// and are available anywhere in the application.
// ============================================================

// ── setting() ─────────────────────────────────────────────────
// Retrieves a system setting value by key from the database.
// Falls back to $default if the key does not exist.
// Usage: setting('daily_visitor_cap', 100)
// ─────────────────────────────────────────────────────────────
if (!function_exists('setting')) {
    // Only define the function if it has not been defined yet
    // (prevents conflicts if this file is included multiple times)
    function setting(string $key, $default = null): mixed
    {
        // Delegate to the SystemSetting model's static get() method
        // which queries the system_settings table for the given key
        return \App\Models\SystemSetting::get($key, $default);
    }
}

// ── status_badge() ────────────────────────────────────────────
// Returns a Bootstrap badge color class for a given status string.
// Used in Blade templates to color-code booking/payment statuses.
// Usage: status_badge('paid') → 'success'
// ─────────────────────────────────────────────────────────────
if (!function_exists('status_badge')) {
    function status_badge(string $status): string
    {
        // Map each possible status value to a Bootstrap color class
        $map = [
            'pending'     => 'warning',   // Yellow — awaiting action
            'paid'        => 'success',   // Green  — payment confirmed
            'checked_in'  => 'info',      // Blue   — guest is on-site
            'checked_out' => 'secondary', // Gray   — guest has left
            'completed'   => 'primary',   // Blue   — booking fully done
            'cancelled'   => 'danger',    // Red    — booking cancelled
            'refunded'    => 'light',     // Light  — payment refunded
            'failed'      => 'danger',    // Red    — payment failed
            'success'     => 'success',   // Green  — payment succeeded
            // Emergency statuses
            'acknowledged'  => 'info',    // Blue   — staff acknowledged
            'responding'    => 'warning', // Yellow — staff en-route
            'resolved'      => 'success', // Green  — emergency resolved
        ];

        // Return the mapped color class, or 'secondary' if status is unknown
        return $map[$status] ?? 'secondary';
    }
}

// ── format_php() ──────────────────────────────────────────────
// Formats a number as Philippine Peso currency string.
// Usage: format_php(1500.00) → 'PHP 1,500.00'
// ─────────────────────────────────────────────────────────────
if (!function_exists('format_php')) {
    function format_php(float|int|string $amount): string
    {
        // Format the number with 2 decimal places and thousands separator,
        // then prefix with the Philippine Peso currency code
        return 'PHP ' . number_format((float) $amount, 2);
    }
}

// ── initials() ────────────────────────────────────────────────
// Extracts the first letter(s) of each word in a name string.
// Used for avatar placeholders when no photo is uploaded.
// Usage: initials('Maria Santos') → 'MS'
// ─────────────────────────────────────────────────────────────
if (!function_exists('initials')) {
    function initials(string $name, int $limit = 2): string
    {
        // Split the full name into individual words
        $words = explode(' ', trim($name));

        // Build the initials string by taking the first character of each word
        $initials = '';
        foreach ($words as $word) {
            // Only proceed if the word is not empty
            if (!empty($word)) {
                // Append the uppercase first character of this word
                $initials .= strtoupper($word[0]);
            }
        }

        // Return only up to the $limit number of initials (default 2)
        return substr($initials, 0, $limit);
    }
}
