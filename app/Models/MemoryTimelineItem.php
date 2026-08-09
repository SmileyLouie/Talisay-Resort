<?php

// ============================================================
// MemoryTimelineItem.php — Eloquent Model for 'memory_timeline_items'
// ============================================================
// Represents an individual memory item (photo, video, or text note)
// uploaded or written by a tourist for their booking memory timeline.
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
    'user_id',     // The ID of the user/tourist who uploaded this memory item
    'booking_id',  // The ID of the booking associated with this memory item
    'type',        // The type of media item: 'photo', 'video', or 'note'
    'file_path',   // Storage path of the uploaded media file (null for text notes)
    'caption',     // The text caption or note content written by the tourist
    'is_selected', // Flag indicating if this item is selected for the compiled PDF
])]
class MemoryTimelineItem extends Model
{
    // Apply the HasFactory trait to enable database seeding and factory creation
    use HasFactory;

    /**
     * Define the data type casts for specific table columns.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Cast is_selected field as boolean (true/false) in PHP
            'is_selected' => 'boolean',
        ];
    }

    /**
     * Define the relationship to the User model (an item belongs to a tourist).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Return user relationship: a memory timeline item belongs to one user
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship to the Booking model (an item belongs to a booking).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking()
    {
        // Return booking relationship: a memory timeline item belongs to one booking
        return $this->belongsTo(Booking::class);
    }
}
