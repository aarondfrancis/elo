<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Notifications\Channels\BroadcastChannel;
use Illuminate\Queue\SerializesModels;

class RatingsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $ratings)
    {
        //
    }

    public function broadcastAs(): string
    {
        return 'ratings.updated';
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('main'),
        ];
    }
}
