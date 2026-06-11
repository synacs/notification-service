<?php

namespace Database\Factories;

use App\Models\NotificationBatch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NotificationBatch>
 */
class NotificationBatchFactory extends Factory
{
    protected $model = NotificationBatch::class;

    public function definition(): array
    {
        return [
            'id' => Str::uuid7(),
            'channel' => 'sms',
            'message' => $this->faker->sentence(10),
            'priority' => $this->faker->numberBetween(0, 10),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
