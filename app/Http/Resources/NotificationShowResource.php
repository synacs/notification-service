<?php

namespace App\Http\Resources;

use App\Models\NotificationBatch;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property NotificationBatch $resource
 */
class NotificationShowResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'batch_id' => $this->resource->id,
            'channel' => $this->resource->channel,
            'message' => $this->resource->message,
            'priority' => $this->resource->priority,
            'total_count' => $this->resource->notifications()->count(),
            'sent_count' => $this->resource->notifications
                ->where('status', 'sent')
                ->count(),
            'processing_count' => $this->resource->notifications
                ->where('status', 'processing')
                ->count(),
            'failed_count' => $this->resource->notifications
                ->where('status', 'failed')
                ->count(),
            'created_at' => $this->resource->created_at->toISOString(),
            'data' => NotificationResource::collection($this->resource->notifications),
        ];
    }
}
