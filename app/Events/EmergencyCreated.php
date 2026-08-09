<?php

namespace App\Events;

use App\Models\Emergency;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class EmergencyCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Emergency $emergency)
    {
        $this->emergency->load('user');
    }

    public function broadcastOn(): array
    {
        return [new Channel('staff-emergencies')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->emergency->id,
            'tracking_number' => $this->emergency->tracking_number,
            'category' => $this->emergency->category,
            'guest_name' => $this->emergency->user->name,
            'description' => $this->emergency->description,
            'latitude' => $this->emergency->latitude,
            'longitude' => $this->emergency->longitude,
            'status' => $this->emergency->status,
            'created_at' => $this->emergency->created_at->toISOString(),
        ];
    }
}
