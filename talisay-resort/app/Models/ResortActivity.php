<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['title', 'description', 'schedule', 'duration', 'meeting_point', 'included', 'what_to_bring', 'price', 'max_guests', 'icon', 'image_path', 'is_active', 'is_bookable', 'sort_order'])]

class ResortActivity extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_bookable' => 'boolean',
            'price'     => 'decimal:2',
            'max_guests' => 'integer',
        ];
    }

    public static function icons(): array
    {
        return [
            'bi-water' => 'Water',
            'bi-sun' => 'Sun',
            'bi-life-preserver' => 'Boat',
            'bi-bicycle' => 'Ride',
            'bi-camera' => 'Photo',
            'bi-people' => 'Group',
            'bi-tree' => 'Nature',
            'bi-cup-hot' => 'Food',
        ];
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? '/storage/'.ltrim($this->image_path, '/') : null;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('title');
    }

    public static function ready(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }

    public static function catalog(): \Illuminate\Support\Collection
    {
        return static::ready() ? static::active()->get() : collect();
    }

    public static function bookable(): \Illuminate\Support\Collection
    {
        if (! static::ready()) {
            return collect();
        }

        return static::active()->where('is_bookable', true)->get();
    }
}
