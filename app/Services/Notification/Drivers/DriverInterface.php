<?php

namespace App\Services\Notification\Drivers;

interface DriverInterface
{
    public function push(callable $callback);
}
