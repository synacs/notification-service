<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

// Массовая рассылка
Route::post('/notifications/bulk', [NotificationController::class, 'bulk'])->middleware([
    \App\Http\Middleware\IdempotencyMiddleware::class,
]);

// Детали пачки уведомлений
Route::get('/notifications/batches/{id}', [NotificationController::class, 'showBatch']);

// Детали конкретного уведомления
Route::get('/notifications/{id}', [NotificationController::class, 'show']);

// История конкретного контакта
Route::get('/notifications/history/{contact}', [NotificationController::class, 'history']);


