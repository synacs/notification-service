<?php

namespace App\Services\Notification\Providers;

use App\Services\Notification\Recipient;

interface ProviderInterface
{
    public function send(Recipient $recipient, string $message): bool;
}
