<?php

namespace App\Services\Notification\Drivers;

class SmsDriver implements DriverInterface
{

    public function push(callable $callback)
    {
        return true;
    }
}
