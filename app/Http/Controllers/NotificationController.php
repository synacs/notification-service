<?php

namespace App\Http\Controllers;

use App\Handlers\BulkQueueForSendingHandler;
use App\Http\Requests\BulkNotificationRequest;
use App\Http\Resources\NotificationBatchResource;
use App\Http\Resources\NotificationHistoryResource;
use App\Http\Resources\NotificationResource;
use App\Http\Resources\NotificationShowResource;
use App\Models\Notification;
use App\Models\NotificationBatch;

class NotificationController extends Controller
{
    public function bulk(
        BulkNotificationRequest    $request,
        BulkQueueForSendingHandler $handler,
    ): NotificationBatchResource
    {
        return NotificationBatchResource::make(
            $handler(
                $request->channel(),
                $request->recipients(),
                $request->message(),
                $request->priority(),
            )
        );
    }

    public function history(string $contact): NotificationHistoryResource
    {
        return new NotificationHistoryResource(
            Notification::where('contact', $contact)
                ->orderBy('created_at', 'desc')
                ->paginate(20)
        );
    }

    public function show(string $id): NotificationResource
    {
        return NotificationResource::make(Notification::findOrFail($id));
    }

    public function showBatch(string $id): NotificationShowResource
    {
        return new NotificationShowResource(
            NotificationBatch::with('notifications')
                ->findOrFail($id)
        );
    }
}
