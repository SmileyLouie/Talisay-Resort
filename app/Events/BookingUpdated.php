<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking) {}

    public function broadcastOn(): array
    {
        $channels = [new PrivateChannel('staff-bookings')];

        if ($this->booking->user_id) {
            $channels[] = new PrivateChannel('guest-booking-' . $this->booking->user_id);
        }

        return $channels;
    }

    public function broadcastAs(): string
    {
        return 'booking.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->booking->id,
            'reference_no' => $this->booking->reference_no,
            'status'       => $this->booking->status,
        ];
    }
}
