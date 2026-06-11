<?php

namespace App\Models;

use App\Services\Notification\Enums\Status;
use App\Traits\HasUuid7;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string|null $batch_id
 * @property string $channel
 * @property string $contact
 * @property string $message
 * @property Status $status
 * @property int $priority
 * @property string|null $error
 * @property string|null $sent_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 *
 * @property-read NotificationBatch|null $batch
 */
class Notification extends Model
{
    use HasFactory, HasUuid7;

    protected $fillable = [
        'batch_id',
        'channel',
        'contact',
        'message',
        'status',
        'priority',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'priority' => 'integer',
        'status' => Status::class,
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }

    public function scopeByBatch($query, $batch): void
    {
        $batchId = $batch instanceof NotificationBatch ? $batch->id : $batch;

        $query->where('batch_id', $batchId);
    }
}
