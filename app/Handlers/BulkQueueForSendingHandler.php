<?php

namespace App\Handlers;

use App\Jobs\SendNotificationJob;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Services\Notification\Enums\RecipientChannel;
use App\Services\Notification\Enums\Status;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * Создаёт batch и уведомления для массовой рассылки, ставит джобы в очередь.
 *
 * Весь процесс выполняется в одной транзакции для обеспечения атомарности.
 *
 * Этапы работы:
 * 1. Создаётся batch (пакет) - контейнер для группы уведомлений.
 * 2. Формируется массив данных для массовой вставки уведомлений (одним INSERT'ом).
 * 3. Каждое уведомление привязывается к batch'у через batch_id.
 * 4. Для каждого созданного уведомления диспатчится SendNotificationJob
 *    в очередь, соответствующую приоритету (high/normal/low).
 * 5. Возвращается созданная модель batch'а для дальнейшего оперирования ей.

 * @throws \Throwable Ошибки транзакции (пробрасываются выше)
 */
final class BulkQueueForSendingHandler
{
    public function __invoke(
        string $channel,
        array  $contacts,
        string $message,
        int    $priority = 0
    ): NotificationBatch
    {
        return DB::transaction(function () use ($channel, $contacts, $message, $priority) {
            $batch = NotificationBatch::create([
                'channel' => $channel,
                'message' => $message,
                'priority' => $priority,
            ]);

            $notifications = array_map(function ($contact) use ($channel, $message, $priority, $batch) {
                return [
                    'id' => Str::uuid7(),
                    'batch_id' => $batch->id,
                    'channel' => RecipientChannel::from($channel)->value,
                    'contact' => $contact,
                    'message' => $message,
                    'status' => Status::PENDING->value,
                    'priority' => $priority,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $contacts);

            Notification::insert($notifications);

            $notifications = Notification::byBatch($batch)->get();

            foreach ($notifications as $notification) {
                $job = (new SendNotificationJob($notification));

                dispatch($job);
            }

            return $batch;
        });
    }

    /**
     * Определяет название очереди в зависимости от приоритета сообщения.
     *
     * Такое разделение гарантирует, что критические сообщения не будут
     * заблокированы в очереди массовыми маркетинговыми рассылками.
     * Воркеры должны быть настроены на приоритетную обработку очередей.
     */
    private function queue(int $priority): string
    {
        return match (true) {
            $priority >= 8 => 'notifications.high',
            $priority >= 4 => 'notifications.normal',
            default => 'notifications.low',
        };
    }
}
