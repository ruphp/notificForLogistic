<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use App\Models\NotificationBatch;
use Illuminate\Support\Collection;

interface NotificationRepositoryInterface
{
    public function firstOrCreateBatch(string $idempotencyKey, array $data): NotificationBatch;

    public function createNotification(array $data): Notification;

    public function findNotificationWithBatch(string $notificationId): ?Notification;

    public function markNotificationSent(Notification $notification, string $providerMessageId): Notification;

    public function markNotificationDelivered(Notification $notification): Notification;

    public function findBatchByIdempotencyKey(string $idempotencyKey): ?NotificationBatch;

    public function getHistoryBySubscriber(string $subscriberId, int $limit): Collection;
}
