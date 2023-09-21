<?php

namespace App\Events;

use App\SaleOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SaleOrderDelivered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $saleOrder;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(SaleOrder $saleOrder)
    {
        $this->saleOrder = $saleOrder;
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
