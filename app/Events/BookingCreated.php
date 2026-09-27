<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->load(['user', 'accommodationUnit']);
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('staff-bookings'),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->booking->id,
            'reference_no' => $this->booking->reference_no,
            'guest_name' => $this->booking->user->name,
            'unit_label'   => $this->booking->accommodationUnit->unit_number ?? 'Resort Accommodation',
            'booking_date' => $this->booking->booking_date->format('Y-m-d'),
            'status' => $this->booking->status,
            'guests_count' => $this->booking->guests_count,
            'created_at' => $this->booking->created_at->toISOString(),
        ];
    }
}
