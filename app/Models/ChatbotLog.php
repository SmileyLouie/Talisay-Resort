<?php

// ============================================================
// ChatbotLog.php — Eloquent Model for 'chatbot_logs' Table
// ============================================================
// Stores history/logs of messages sent by users to the chatbot
// and the corresponding replies returned by the system.
// Used for chat history analysis and intent optimization.
// ============================================================

namespace App\Models;

// Import the Fillable attribute for declaring mass-assignable model attributes
use Illuminate\Database\Eloquent\Attributes\Fillable;

// Import HasFactory for factory/seeding support in database operations
use Illuminate\Database\Eloquent\Factories\HasFactory;

// Import the base Eloquent Model class from the framework
use Illuminate\Database\Eloquent\Model;

// Declare the attributes that are mass-assignable via model creation methods
#[Fillable([
    'user_id',    // The ID of the user who engaged with the chatbot (nullable for guests)
    'session_id', // Unique session identifier for tracking continuous conversations
    'message',    // The message typed by the user/tourist
    'response',   // The response content delivered by the chatbot
    'intent',     // The matched keyword or category intent triggering the response
])]
class ChatbotLog extends Model
{
    // Apply the HasFactory trait to enable database seeding and factory creation
    use HasFactory;

    /**
     * Define the relationship to the User model (a log entry belongs to a tourist).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Return user relationship: a chatbot log entry belongs to one user
        return $this->belongsTo(User::class);
    }
}
