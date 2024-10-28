<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class UserActionEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;
    public $action;

    public function __construct($user, $action)
    {
        $this->user = $user;  // The user object
        $this->action = $action;  // The type of action (create, update, delete)
    }

    public function broadcastOn()
    {
        return ['user-channel'];  // Specify your channel
    }

    public function broadcastAs()
    {
        return 'user-action';  // The event name
    }
}
