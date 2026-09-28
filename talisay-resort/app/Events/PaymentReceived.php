<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Payment $payment) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('admin-payments')];
    }

    public function broadcastAs(): string
    {
        return 'payment.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id'           => $this->payment->id,
            'amount'       => (float) $this->payment->amount,
            'booking_id'   => $this->payment->booking_id,
            'reference_no' => $this->payment->booking?->reference_no,
            'gateway'      => $this->payment->gateway,
            'status'       => $this->payment->status,
        ];
    }
}
