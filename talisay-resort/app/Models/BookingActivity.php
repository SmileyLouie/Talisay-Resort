<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['booking_id', 'resort_activity_id', 'title', 'price', 'quantity'])]

class BookingActivity extends Model
{
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }

    public function activity()
    {
        return $this->belongsTo(ResortActivity::class, 'resort_activity_id');
    }

    public function lineTotal(): float
    {
        return round((float) $this->price * max(1, (int) $this->quantity), 2);
    }
}
