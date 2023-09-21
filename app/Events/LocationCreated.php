<?php

namespace App\Events;

use App\Location;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LocationCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $location;

    /**
     * Create a new event instance.
     *
     * @param  Location  $location
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
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
