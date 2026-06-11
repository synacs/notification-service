<?php

namespace Database\Factories;

use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Services\Notification\Enums\Status;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Notification>
 */
class NotificationFactory extends Factory
{
    protected $model = Notification::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid7(),
            'batch_id' => null,
            'channel' => 'sms',
            'contact' => '+7' . $this->faker->numerify('##########'),
            'message' => $this->faker->sentence(10),
            'status' => Status::PENDING,
            'priority' => $this->faker->numberBetween(0, 10),
            'error' => null,
            'sent_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function withStatus(Status $status): self
    {
        return $this->state([
            'status' => $status,
        ]);
    }

    public function withPhone(): self
    {
        return $this->state([
            'channel' => 'sms',
            'contact' => '+7' . $this->faker->numerify('##########'),
        ]);
    }

    public function withEmail(): self
    {
        return $this->state([
            'channel' => 'email',
            'contact' => $this->faker->unique()->safeEmail(),
        ]);
    }
}
