<?php

namespace App\Events;

use App\Entities\User;
use Core\Events\Event;

/**
 * This event is dispatched when a new user successfully registers.
 */
class UserRegistered extends Event
{
    /**
     * @param User $user The newly created user entity.
     */
    public function __construct(public readonly User $user)
    {
    }
}
