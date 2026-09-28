<?php

// ============================================================
// ChatbotIntent.php — Model for the 'chatbot_intents' Table
// ============================================================
// Represents a keyword-response pair for the resort chatbot.
// The chatbot matches guest messages against keywords and returns
// the associated response. Intents are grouped by category.
// Admin can manage these intents through the Chatbot panel.
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
    'keyword',   // The trigger phrase the chatbot tries to match in guest messages
    'response',  // The automated reply text returned when the keyword is matched
    'category',  // Grouping category: 'rates'|'hours'|'booking_help'|'facilities'|'policies'|'general'|'directions'
    'is_active', // Whether this intent is currently active and used in matching
])]

/**
 * Class ChatbotIntent
 *
 * Represents one keyword → response pair for the resort's automated chatbot.
 * The chatbot controller (ChatbotController) looks up matching intents
 * by checking if the guest's message contains the stored keyword.
 *
 * @property int    $id        Auto-incrementing primary key
 * @property string $keyword   Trigger keyword phrase (case-insensitive match)
 * @property string $response  Pre-written response text for this intent
 * @property string $category  Organizational category for admin display
 * @property bool   $is_active Whether this intent is currently enabled
 */
class ChatbotIntent extends Model
{
    // Enable factory support for seeding and testing
    use HasFactory;

    public const CATEGORIES = ['rates', 'hours', 'policies', 'directions', 'facilities', 'booking_help', 'general'];

    /**
     * Comma-separated keyword phrases as a clean array.
     *
     * @return array<int, string>
     */
    public function keywordList(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', strtolower((string) $this->keyword)))));
    }

    /**
     * Define type casts for model attributes.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast is_active from integer (0/1) to boolean (true/false)
            'is_active' => 'boolean',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    /**
     * Scope: Filter to only active chatbot intents.
     * The ChatbotController uses this scope when matching guest messages,
     * so disabled intents are never matched.
     *
     * @param  \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        // Add WHERE is_active = true to exclude disabled intents from matching
        return $query->where('is_active', true);
    }
}
