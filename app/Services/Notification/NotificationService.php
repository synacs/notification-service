<?php

namespace App\Services\Notification;

use App\Services\Notification\Providers\ProviderInterface;
use RuntimeException;

/**
 * @property ProviderInterface[] $providers
 */
class NotificationService
{
    private array $providers = [];

    public function addProvider(ProviderInterface $provider): NotificationService
    {
        if(isset($this->providers[$provider::class])) {
            throw new RuntimeException(
                sprintf('Провайдер %s уже существует.', $provider::class)
            );
        }

        $this->providers[$provider::class] = $provider;

        return $this;
    }

    public function send(Recipient $recipient, string $message): bool
    {
        if(!isset($this->providers[$recipient->channel->getProviderClass()])) {
            throw new RuntimeException("Провайдер для типа '{$recipient->channel->name}' не зарегистрирован.");
        }

        return $this->providers[$recipient->channel->getProviderClass()]
            ?->send($recipient, $message);
    }
}
