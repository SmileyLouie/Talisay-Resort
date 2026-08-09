<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function broadcastOn(): array
    {
        return [new Channel('admin-payments')];
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'booking_id' => $this->payment->booking_id,
            'gateway' => $this->payment->gateway,
            'status' => $this->payment->status,
        ];
    }
}
