<?php

// ============================================================
// MemoryTimeline.php — Eloquent Model for 'memory_timelines' Table
// ============================================================
// Represents a collection of memory timeline items generated as a PDF report
// for tourists to remember their beach resort visit.
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
    'user_id',            // The ID of the user/tourist who owns this timeline
    'booking_id',         // The ID of the specific resort booking this timeline is for
    'title',              // The customized title of the memory timeline
    'generated_pdf_path', // The storage file path where the generated PDF is saved
    'is_generated',       // Flag indicating if the PDF has been compiled/generated
])]
class MemoryTimeline extends Model
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
            // Cast is_generated field as boolean (true/false) in PHP
            'is_generated' => 'boolean',
        ];
    }

    /**
     * Define the relationship to the User model (a timeline belongs to a tourist).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        // Return user relationship: a timeline belongs to one user
        return $this->belongsTo(User::class);
    }

    /**
     * Define the relationship to the Booking model (a timeline belongs to a booking).
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function booking()
    {
        // Return booking relationship: a timeline belongs to one booking
        return $this->belongsTo(Booking::class);
    }

    /**
     * Define a relationship to access individual media items associated with the booking.
     * Uses a HasManyThrough relationship via the Bookings table.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function items()
    {
        // Return the relationship: memory timeline items retrieved through the booking
        return $this->hasManyThrough(
            MemoryTimelineItem::class, // The final target model (MemoryTimelineItem)
            Booking::class,            // The intermediate model (Booking)
            'id',                      // Local key on the intermediate table (bookings.id)
            'booking_id',              // Foreign key on the target table (memory_timeline_items.booking_id)
            'booking_id',              // Local key on the parent table (memory_timelines.booking_id)
            'id'                       // Local key on the intermediate table (bookings.id)
        );
    }
}
