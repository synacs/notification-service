<?php

namespace App\Http\Resources;

use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Notification $resource
 */
class NotificationResource extends JsonResource
{
    public static $wrap = false;

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'batch_id' => $this->resource->batch_id,
            'channel' => $this->resource->channel,
            'contact' => $this->resource->contact,
            'message' => $this->resource->message,
            'status' => $this->resource->status->value,
            'priority' => $this->resource->priority,
            'error' => $this->resource->error,
            'sent_at' => $this->resource->sent_at?->toISOString(),
            'created_at' => $this->resource->created_at->toISOString(),
            'updated_at' => $this->resource->updated_at->toISOString(),
        ];
    }
}
