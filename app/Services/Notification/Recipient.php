<?php

namespace App\Services\Notification;

use App\Services\Notification\Enums\RecipientChannel;

final readonly class Recipient
{
    public function __construct(
        public RecipientChannel $channel,
        public string           $contact,
    )
    {
    }
}
