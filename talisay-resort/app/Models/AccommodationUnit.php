<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
    'price_per_hour',
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
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'amenities'       => 'array',
            'images'          => 'array',
            'is_available'    => 'boolean',
            'price_per_night' => 'decimal:2',
            'price_per_hour'  => 'decimal:2',
        ];
    }

    /**
     * A blank floor area is stored as null. An empty string cannot be cast as a decimal
     * and was crashing the rooms page after a unit was saved.
     */
    protected function floorAreaSqm(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($value === null || $value === '') {
                    return null;
                }

                return number_format((float) $value, 1, '.', '');
            },
            set: fn ($value) => ($value === null || $value === '') ? null : $value,
        );
    }

    // ──────────────────────────────────────────────────────────
    // RELATIONSHIPS
    // ──────────────────────────────────────────────────────────

    /**
     * The hourly rate saved on the unit. Older units without one use one twelfth of the nightly rate.
     */
    public function hourlyRate(): float
    {
        if ($this->price_per_hour !== null && $this->price_per_hour !== '') {
            return round((float) $this->price_per_hour, 2);
        }

        return round((float) $this->price_per_night / 12, 2);
    }

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
        return UnitVariant::labelFor($this->variant);
    }

    public function isDeluxeVariant(): bool
    {
        return UnitVariant::isDeluxe($this->variant);
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

    public function scopeNormal($query)
    {
        $slugs = UnitVariant::ready()
            ? UnitVariant::query()->where('is_deluxe', false)->pluck('slug')->all()
            : ['normal'];

        return $query->whereIn('variant', $slugs ?: ['normal']);
    }

    public function scopePremium($query)
    {
        $slugs = UnitVariant::ready()
            ? UnitVariant::query()->where('is_deluxe', true)->pluck('slug')->all()
            : ['premium'];

        return $query->whereIn('variant', $slugs ?: ['premium']);
    }
}
