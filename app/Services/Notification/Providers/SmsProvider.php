<?php

namespace App\Services\Notification\Providers;

use App\Services\Notification\Drivers\DriverInterface;
use App\Services\Notification\Recipient;

class SmsProvider implements ProviderInterface
{
    private DriverInterface $driver;

    public function __construct(DriverInterface $driver)
    {
        $this->driver = $driver;
    }

    public function send(Recipient $recipient, string $message): bool
    {
        return $this->driver->push(fn() => true);
    }
}
