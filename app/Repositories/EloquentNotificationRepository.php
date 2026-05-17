<?php

namespace App\Repositories;

use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function firstOrCreateBatch(string $idempotencyKey, array $data): NotificationBatch
    {
        return NotificationBatch::query()->firstOrCreate(
            ['idempotency_key' => $idempotencyKey],
            $data,
        );
    }

    public function createNotification(array $data): Notification
    {
        return Notification::query()->create($data);
    }

    public function findNotificationWithBatch(string $notificationId): ?Notification
    {
        return Notification::query()
            ->with('batch')
            ->find($notificationId);
    }

    public function markNotificationSent(Notification $notification, string $providerMessageId): Notification
    {
        $notification->forceFill([
            'status' => NotificationStatus::Sent,
            'attempts' => $notification->attempts + 1,
            'provider_message_id' => $providerMessageId,
            'sent_at' => now(),
            'last_error' => null,
        ])->save();

        return $notification;
    }

    public function markNotificationDelivered(Notification $notification): Notification
    {
        $notification->forceFill([
            'status' => NotificationStatus::Delivered,
            'delivered_at' => now(),
        ])->save();

        return $notification;
    }

    public function markNotificationAttemptFailed(Notification $notification, string $error): Notification
    {
        $notification->forceFill([
            'attempts' => $notification->attempts + 1,
            'last_error' => $error,
        ])->save();

        return $notification;
    }

    public function markNotificationDropped(Notification $notification, string $error): Notification
    {
        $notification->forceFill([
            'status' => NotificationStatus::Dropped,
            'attempts' => $notification->attempts + 1,
            'last_error' => $error,
            'dropped_at' => now(),
        ])->save();

        return $notification;
    }

    public function findBatchByIdempotencyKey(string $idempotencyKey): ?NotificationBatch
    {
        return NotificationBatch::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();
    }

    public function getHistoryBySubscriber(string $subscriberId, int $limit): Collection
    {
        return Notification::query()
            ->with('batch:id,message')
            ->where('recipient_id', $subscriberId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
