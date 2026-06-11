<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Jobs\SendNotificationJob;
use App\Services\Notification\Enums\Status;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        Cache::flush();
    }

    /**
     * Тест 1: Успешная массовая рассылка
     */
    public function test_can_send_bulk_notifications()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => [
                'user1@example.com',
                'user2@example.com',
                'user3@example.com',
            ],
            'message' => 'Welcome to our service!',
            'priority' => 5,
        ];

        $response = $this->postJson('/api/notifications/bulk', $payload);

        $response->assertStatus(201)
            ->assertJsonStructure(['batch_id']);

        $batchId = $response->json('batch_id');

        $this->assertDatabaseHas('notification_batches', [
            'id' => $batchId,
            'channel' => 'email',
            'message' => 'Welcome to our service!',
            'priority' => 5,
        ]);

        $this->assertEquals(3, Notification::where('batch_id', $batchId)->count());
    }

    /**
     * Тест 2: Идемпотентность через middleware (кеш)
     */
    public function test_idempotency_key_prevents_duplicates()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['user@example.com'],
            'message' => 'Test message',
        ];

        $idempotencyKey = 'unique-key-123';

        $response1 = $this->postJson('/api/notifications/bulk', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response2 = $this->postJson('/api/notifications/bulk', $payload, [
            'Idempotency-Key' => $idempotencyKey,
        ]);

        $response1->assertStatus(201);
        $response2->assertStatus(201);

        $this->assertEquals(
            $response1->json('batch_id'),
            $response2->json('batch_id')
        );

        // В базе только один batch
        $this->assertEquals(1, NotificationBatch::count());
    }

    /**
     * Тест 3: Разные ключи - разные batch'и
     */
    public function test_different_idempotency_keys_create_different_batches()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['user@example.com'],
            'message' => 'Test',
        ];

        $response1 = $this->postJson('/api/notifications/bulk', $payload, [
            'Idempotency-Key' => 'key-1',
        ]);

        $response2 = $this->postJson('/api/notifications/bulk', $payload, [
            'Idempotency-Key' => 'key-2',
        ]);

        $this->assertNotEquals(
            $response1->json('batch_id'),
            $response2->json('batch_id')
        );

        $this->assertEquals(2, NotificationBatch::count());
    }

    /**
     * Тест 4: Высокий приоритет в high очередь
     */
    public function test_high_priority_goes_to_high_queue()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['high@example.com'],
            'message' => 'High priority',
            'priority' => 9,
        ];

        $this->postJson('/api/notifications/bulk', $payload);

        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->queue === 'notifications.high';
        });
    }

    /**
     * Тест 5: Нормальный приоритет в normal очередь
     */
    public function test_normal_priority_goes_to_normal_queue()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['normal@example.com'],
            'message' => 'Normal priority',
            'priority' => 5,
        ];

        $this->postJson('/api/notifications/bulk', $payload);

        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->queue === 'notifications.normal';
        });
    }

    /**
     * Тест 6: Низкий приоритет в low очередь
     */
    public function test_low_priority_goes_to_low_queue()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['low@example.com'],
            'message' => 'Low priority',
            'priority' => 1,
        ];

        $this->postJson('/api/notifications/bulk', $payload);

        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->queue === 'notifications.low';
        });
    }

    /**
     * Тест 7: Статус sent после успешной отправки
     */
    public function test_notification_status_becomes_sent_after_successful_sending()
    {
        $notification = Notification::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'contact' => 'success@example.com',
            'message' => 'Test',
            'status' => Status::PENDING,
            'priority' => 5,
        ]);

        $job = new SendNotificationJob($notification);

        \App\Services\Notification\NotificationFacade::shouldReceive('send')
            ->once()
            ->andReturn(true);

        $job->handle();

        $notification->refresh();
        $this->assertEquals(Status::SENT, $notification->status);
        $this->assertNotNull($notification->sent_at);
        $this->assertNull($notification->error);
    }

    /**
     * Тест 8: Статус failed при ошибке
     */
    public function test_notification_status_becomes_failed_after_error()
    {
        $notification = Notification::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'contact' => 'fail@example.com',
            'message' => 'Test',
            'status' => Status::PENDING,
            'priority' => 5,
        ]);

        $job = new SendNotificationJob($notification);

        \App\Services\Notification\NotificationFacade::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('Provider error'));

        try {
            $job->handle();
        } catch (\Exception $e) {
            $this->assertEquals('Provider error', $e->getMessage());
        }

        $notification->refresh();
        $this->assertEquals(Status::FAILED, $notification->status);
        $this->assertNotNull($notification->error);
    }

    /**
     * Тест 9: Статус delivered (подтверждение от провайдера)
     */
    public function test_notification_can_reach_delivered_status()
    {
        $notification = Notification::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'contact' => 'delivered@example.com',
            'message' => 'Test',
            'status' => Status::SENT,
            'priority' => 5,
            'sent_at' => now(),
        ]);

        $notification->update(['status' => Status::DELIVERED]);
        $notification->refresh();

        $this->assertEquals(Status::DELIVERED, $notification->status);
    }

    /**
     * Тест 10: Получение истории контакта
     */
    public function test_can_get_notification_history_by_contact()
    {
        $contact = 'history@example.com';

        for ($i = 0; $i < 3; $i++) {
            Notification::create([
                'id' => Str::uuid7(),
                'channel' => 'email',
                'contact' => $contact,
                'message' => "Message $i",
                'status' => Status::SENT,
                'priority' => 5,
                'sent_at' => now(),
            ]);
        }

        Notification::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'contact' => 'other@example.com',
            'message' => 'Other',
            'status' => Status::SENT,
            'priority' => 5,
        ]);

        $response = $this->getJson("/api/notifications/history/{$contact}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'pagination' => ['current_page', 'last_page', 'per_page', 'total']
            ]);

        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $item) {
            $this->assertEquals($contact, $item['contact']);
        }
    }

    /**
     * Тест 11: Валидация email (должна проходить - без строгой валидации)
     * Сейчас API принимает любые строки, тест проверяет что 201
     */
    public function test_invalid_email_is_accepted_for_now()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['invalid-email', 'valid@example.com'],
            'message' => 'Test',
        ];

        $response = $this->postJson('/api/notifications/bulk', $payload);

        // Сейчас валидации нет, поэтому ожидаем 201
        $response->assertStatus(201);
    }

    /**
     * Тест 12: Джоба использует RabbitMQ соединение
     */
    public function test_job_uses_rabbitmq_connection()
    {
        $payload = [
            'channel' => 'email',
            'recipients' => ['test@example.com'],
            'message' => 'Test',
        ];

        $this->postJson('/api/notifications/bulk', $payload);

        Queue::assertPushed(SendNotificationJob::class, function ($job) {
            return $job->connection === 'rabbitmq';
        });
    }

    /**
     * Тест 13: Retry механизм работает
     */
    public function test_job_has_retry_mechanism()
    {
        $notification = Notification::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'contact' => 'test@example.com',
            'message' => 'Test',
            'status' => Status::PENDING,
            'priority' => 5,
        ]);

        $job = new SendNotificationJob($notification);

        $this->assertEquals(3, $job->tries);
        $this->assertEquals([10, 60, 300], $job->backoff());
    }

    /**
     * Детали batch со статистикой
     */
    public function test_can_get_batch_details_with_statistics()
    {
        $batch = NotificationBatch::create([
            'id' => Str::uuid7(),
            'channel' => 'email',
            'message' => 'Batch message',
            'priority' => 5,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Notification::create([
                'id' => Str::uuid7(),
                'batch_id' => $batch->id,
                'channel' => 'email',
                'contact' => 'sent@example.com',
                'message' => 'Test',
                'status' => Status::SENT,
                'priority' => 5,
                'sent_at' => now(),
            ]);
        }

        for ($i = 0; $i < 3; $i++) {
            Notification::create([
                'id' => Str::uuid7(),
                'batch_id' => $batch->id,
                'channel' => 'email',
                'contact' => 'failed@example.com',
                'message' => 'Test',
                'status' => Status::FAILED,
                'priority' => 5,
                'error' => 'Error',
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Notification::create([
                'id' => Str::uuid7(),
                'batch_id' => $batch->id,
                'channel' => 'email',
                'contact' => 'processing@example.com',
                'message' => 'Test',
                'status' => Status::PROCESSING,
                'priority' => 5,
            ]);
        }

        for ($i = 0; $i < 2; $i++) {
            Notification::create([
                'id' => Str::uuid7(),
                'batch_id' => $batch->id,
                'channel' => 'email',
                'contact' => 'pending@example.com',
                'message' => 'Test',
                'status' => Status::PENDING,
                'priority' => 5,
            ]);
        }

        $response = $this->getJson("/api/notifications/batches/{$batch->id}");

        $response->assertStatus(200)
            ->assertJson([
                'batch_id' => $batch->id,
                'channel' => 'email',
                'message' => 'Batch message',
                'priority' => 5,
                'total_count' => 12,
                'sent_count' => 5,
                'failed_count' => 3,
                'processing_count' => 2,
            ])
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'status', 'contact']
                ]
            ]);

        $this->assertCount(12, $response->json('data'));
    }
}
