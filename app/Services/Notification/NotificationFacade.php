<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool send(Recipient $recipient, string $message)
 */
class NotificationFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationService::class;
    }
}
