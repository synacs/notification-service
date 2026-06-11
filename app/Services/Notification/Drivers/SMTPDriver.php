<?php

namespace App\Services\Notification\Drivers;

class SMTPDriver implements DriverInterface
{
    public function push(callable $callback)
    {
        return true;
    }
}
