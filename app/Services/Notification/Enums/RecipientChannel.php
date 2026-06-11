<?php

namespace App\Services\Notification\Enums;

use App\Services\Notification\Providers\EmailProvider;
use App\Services\Notification\Providers\SmsProvider;

enum RecipientChannel: string
{
    case SMS = 'sms';
    case EMAIL = 'email';

    public function getProviderClass(): string
    {
        return match($this) {
            self::SMS => SmsProvider::class,
            self::EMAIL => EmailProvider::class,
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
