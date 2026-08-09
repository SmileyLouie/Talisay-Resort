<?php

namespace App\Events;

use App\Models\Emergency;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Emergency $emergency) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('staff-emergencies'),
            new Channel('guest-emergency-' . $this->emergency->user_id),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->emergency->id,
            'tracking_number' => $this->emergency->tracking_number,
            'status' => $this->emergency->status,
            'response_notes' => $this->emergency->response_notes,
        ];
    }
}
