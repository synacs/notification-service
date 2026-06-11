<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Services\Notification\Enums\RecipientChannel;
use App\Services\Notification\Enums\Status;
use App\Services\Notification\NotificationFacade;
use App\Services\Notification\Recipient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendNotificationJob implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Количество попыток отправки.
     * At-least-once гарантируется самой очередью.
     */
    public int $tries = 3;

    /**
     * Задержки между попытками (экспоненциальная).
     * 10 сек, 60 сек, 300 сек.
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function __construct(private Notification $notification)
    {
        $this->onConnection('rabbitmq');
        $this->onQueue($this->getQueueName());
    }

    private function getQueueName(): string
    {
        return match (true) {
            $this->notification->priority >= 8 => 'notifications.high',
            $this->notification->priority >= 4 => 'notifications.normal',
            default => 'notifications.low',
        };
    }

    /**
     * Redis-блокировка предотвращает параллельную обработку одного уведомления.
     * Без неё два воркера могут одновременно взять один и тот же job.
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("notification:{$this->notification->id}"))
                ->expireAfter(60)
                ->releaseAfter(30)
        ];
    }

    public function handle(): void
    {
        if (in_array($this->notification->status, [Status::SENT, Status::DELIVERED])) {
            Log::info("Уведомление {$this->notification->id} уже отправлено.");
            return;
        }

        $this->notification->update(['status' => Status::PROCESSING]);

        $recipient = new Recipient(
            RecipientChannel::from($this->notification->channel),
            $this->notification->contact
        );

        try {
            $success = NotificationFacade::send($recipient, $this->notification->message);

            if ($success) {
                $this->notification->update([
                    'status' => Status::SENT,
                    'sent_at' => now(),
                    'error' => null,
                ]);

                Log::info("Уведомление {$this->notification->id} успешно отправлено");
            } else {
                throw new \RuntimeException('Провайдер вернул ошибку');
            }

        } catch (Throwable $e) {
            $this->notification->update([
                'status' => Status::FAILED,
                'error' => $e->getMessage(),
            ]);

            Log::error("Ошибка отправки {$this->notification->id}: " . $e->getMessage());

            throw $e;
        }
    }
}
