<?php

namespace App\Repositories;

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
