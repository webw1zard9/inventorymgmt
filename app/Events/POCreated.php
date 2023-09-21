<?php

namespace App\Events;

use App\PurchaseOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class POCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $purchaseOrder;

    /**
     * Create a new event instance.
     *
     * @param  PurchaseOrder  $purchaseOrder
     */
    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
