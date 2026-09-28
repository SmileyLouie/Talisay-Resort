<?php

namespace App\Events;

use App\Models\CapacitySchedule;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Aggregate occupancy for a date. Contains no personal data, so it is
 * intentionally public (used by the availability calendar).
 */
class CapacityUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public CapacitySchedule $capacity) {}

    public function broadcastOn(): array
    {
        return [new Channel('capacity')];
    }

    public function broadcastAs(): string
    {
        return 'capacity.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'date'          => $this->capacity->date->format('Y-m-d'),
            'current_count' => $this->capacity->current_count,
            'max_capacity'  => $this->capacity->max_capacity,
            'utilization'   => $this->capacity->getUtilizationPercent(),
        ];
    }
}
