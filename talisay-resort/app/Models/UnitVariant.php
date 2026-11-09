<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

#[Fillable(['slug', 'label', 'price_per_night', 'applies_to', 'is_deluxe', 'is_system', 'is_active', 'sort_order'])]

class UnitVariant extends Model
{
    protected function casts(): array
    {
        return [
            'price_per_night' => 'decimal:2',
            'is_deluxe' => 'boolean',
            'is_system' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('label');
    }

    public static function ready(): bool
    {
        return Schema::hasTable((new static)->getTable());
    }

    public static function forKind(string $kind): \Illuminate\Support\Collection
    {
        if (! static::ready()) {
            return collect([
                (object) ['slug' => 'normal', 'label' => 'Normal', 'is_deluxe' => false],
                (object) ['slug' => 'premium', 'label' => 'Deluxe', 'is_deluxe' => true],
            ]);
        }

        return static::active()
            ->where(fn ($q) => $q->where('applies_to', 'both')->orWhere('applies_to', $kind))
            ->get();
    }

    public static function labelFor(?string $slug): string
    {
        $slug = (string) $slug;
        if ($slug === 'premium') {
            return 'Deluxe';
        }
        if ($slug === 'normal') {
            return 'Normal';
        }
        if (! static::ready()) {
            return $slug !== '' ? Str::headline($slug) : '';
        }

        return static::query()->where('slug', $slug)->value('label') ?: Str::headline($slug);
    }

    public static function isDeluxe(?string $slug): bool
    {
        $slug = (string) $slug;
        if ($slug === 'premium') {
            return true;
        }
        if (! static::ready()) {
            return (bool) preg_match('/deluxe|premium/i', $slug);
        }

        return (bool) static::query()->where('slug', $slug)->value('is_deluxe');
    }

    public static function activeSlugs(?string $kind = null): array
    {
        return static::forKind($kind ?: 'room')->pluck('slug')->all();
    }
}
