<?php

namespace App\Events\Auth;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserRegistered
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public Authenticatable $user;

    public function __construct(Authenticatable $user)
    {
        $this->user = $user;
    }
}
