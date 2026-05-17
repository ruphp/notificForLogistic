<?php

use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => 'Notification Service',
    'status' => 'ok',
    'endpoints' => [
        'create_bulk_notification' => 'POST /api/notifications/bulk',
        'subscriber_history' => 'GET /api/subscribers/{subscriberId}/notifications',
    ],
]));

Route::middleware('client')->group(function (): void {
    Route::post('/notifications/bulk', [NotificationController::class, 'store'])
        ->name('notifications.bulk');

    Route::get('/subscribers/{subscriberId}/notifications', [NotificationController::class, 'bySubscriber'])
        ->name('subscribers.notifications');
});
