<?php

namespace App\Events;

use App\Batch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchAllocated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $batch;

    public $batch_allocation_data;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Batch $batch, $batch_allocation_data)
    {
        $this->batch = $batch;
        $this->batch_allocation_data = $batch_allocation_data;
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
