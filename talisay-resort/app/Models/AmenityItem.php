<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

#[Fillable(['name', 'icon', 'is_active', 'sort_order'])]

class AmenityItem extends Model
{
    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    public static function catalog(): \Illuminate\Support\Collection
    {
        if (! Schema::hasTable((new static)->getTable())) {
            return collect();
        }

        return static::active()->get();
    }
}
