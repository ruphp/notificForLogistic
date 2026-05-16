<?php

namespace App\Services;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Collection;

class NotificationHistoryService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function bySubscriber(string $subscriberId, int $limit): Collection
    {
        return $this->notifications
            ->getHistoryBySubscriber($subscriberId, $limit)
            ->map(fn (Notification $notification): array => [
                'id' => $notification->id,
                'batch_id' => $notification->batch_id,
                'channel' => $notification->channel->value,
                'priority' => $notification->priority->value,
                'status' => $notification->status->value,
                'attempts' => $notification->attempts,
                'message' => $notification->batch?->message,
                'provider_message_id' => $notification->provider_message_id,
                'last_error' => $notification->last_error,
                'queued_at' => $notification->created_at?->toISOString(),
                'sent_at' => $notification->sent_at?->toISOString(),
                'delivered_at' => $notification->delivered_at?->toISOString(),
                'dropped_at' => $notification->dropped_at?->toISOString(),
            ]);
    }
}
