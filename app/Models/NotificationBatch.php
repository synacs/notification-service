<?php

namespace App\Models;

use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $channel
 * @property string $message
 * @property int $priority
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read Collection<int, Notification> $notifications
 */
class NotificationBatch extends Model
{
    use HasFactory, HasUuid7;

    protected $fillable = [
        'channel',
        'message',
        'priority',
    ];

    protected $casts = [
        'priority' => 'integer',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }
}
