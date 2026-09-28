<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Booking $booking)
    {
        $this->booking->loadMissing(['user', 'accommodationUnit']);
    }

    public function broadcastOn(): array
    {
        return [new PrivateChannel('staff-bookings')];
    }

    public function broadcastAs(): string
    {
        return 'booking.created';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->booking->id,
            'reference_no' => $this->booking->reference_no,
            'guest_name'   => $this->booking->guest_name,
            'unit_label'   => $this->booking->isSpecialResort()
                ? 'Full Resort'
                : ($this->booking->accommodationUnit?->unit_number ?? 'Resort Accommodation'),
            'booking_date' => $this->booking->checkInDate()->format('Y-m-d'),
            'status'       => $this->booking->status,
            'guests_count' => $this->booking->guests_count,
            'created_at'   => $this->booking->created_at?->toISOString(),
        ];
    }
}
