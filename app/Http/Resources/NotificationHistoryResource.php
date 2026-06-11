<?php

namespace App\Http\Resources;

use App\Models\NotificationBatch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LengthAwarePaginator $resource
 */
class NotificationHistoryResource extends JsonResource
{
    public static $wrap = null;

    public function toArray(Request $request): array
    {
        return [
            'data' => NotificationResource::collection($this->resource->items()),
            'pagination' => PaginationResource::make($this->resource),
        ];
    }
}
