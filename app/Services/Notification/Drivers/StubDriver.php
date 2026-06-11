<?php

namespace App\Services\Notification\Drivers;

class StubDriver implements DriverInterface
{

    public function push(callable $callback)
    {
        return true;
    }
}
