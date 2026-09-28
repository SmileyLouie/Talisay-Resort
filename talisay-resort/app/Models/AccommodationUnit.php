<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'unit_number',
    'unit_type',
    'variant',
    'floor_area_sqm',
    'bed_configuration',
    'max_occupancy',
    'amenities',
    'description',
    'images',
    'tour_video_path',
    'price_per_night',
    'is_available',
    'sort_order',
])]

/**
 * Class AccommodationUnit
 *
 * Represents an individual room or cottage at Talisay Beach Resort.
 * Each unit has its own description, photo gallery, and optional 360° tour video.
 */
class AccommodationUnit extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'amenities'       => 'array',
            'images'          => 'array',
            'is_available'    => 'boolean',
            'price_per_night' => 'decimal:2',
            'floor_area_sqm'  => 'decimal:1',
        ];
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    // ──────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────

    public function getTypeLabelAttribute(): string
    {
        return match($this->unit_type) {
            'room'    => 'Room',
            'cottage' => 'Cottage',
            default   => ucfirst($this->unit_type),
        };
    }

    public function getVariantLabelAttribute(): string
    {
        return match($this->variant) {
            'normal'  => 'Normal',
            'premium' => 'Premium',
            default   => ucfirst($this->variant),
        };
    }

    public function getFirstImageAttribute(): ?string
    {
        if (!empty($this->images) && is_array($this->images)) {
            return $this->images[0];
        }
        return null;
    }

    // ──────────────────────────────────────────────────────────
    // QUERY SCOPES
    // ──────────────────────────────────────────────────────────

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }

    public function scopeRooms($query)
    {
        return $query->where('unit_type', 'room');
    }

    public function scopeCottages($query)
    {
        return $query->where('unit_type', 'cottage');
    }

    public function scopePremium($query)
    {
        return $query->where('variant', 'premium');
    }
}
