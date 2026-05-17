<?php

namespace App\Services;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\NotificationBatch;
use App\Queue\Contracts\NotificationQueuePublisherInterface;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

class BulkNotificationService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly NotificationQueuePublisherInterface $queue,
        private readonly RedisIdempotencyLock $idempotencyLock,
    ) {
    }

    // Создает пачку рассылки и отдельные уведомления для каждого получателя.
    public function create(
        string $idempotencyKey,
        string $channel,
        string $priority,
        string $message,
        array $recipientIds,
    ): NotificationBatch {
        $notificationIds = [];
        $hasLock = $this->idempotencyLock->tryLock($idempotencyKey);// и ставим лок на 30 сек

        if (!$hasLock) { //скорее всего уже обработан, ищем
            $batch = $this->notifications->findBatchByIdempotencyKey($idempotencyKey);

            if ($batch) {
                return $batch;
            }
        }

        try {
            $batch = DB::transaction(function () use ($idempotencyKey, $channel, $priority, $message, $recipientIds, &$notificationIds): NotificationBatch {
                $batch = $this->notifications->firstOrCreateBatch($idempotencyKey, [
                    'channel' => NotificationChannel::from($channel),
                    'priority' => NotificationPriority::from($priority),
                    'message' => $message,
                    'recipient_count' => count($recipientIds),
                ]);

                if (!$batch->wasRecentlyCreated) {
                    return $batch;
                }

                foreach ($recipientIds as $recipientId) {
                    $notification = $this->notifications->createNotification([
                        'batch_id' => $batch->id,
                        'recipient_id' => $recipientId,
                        'channel' => NotificationChannel::from($channel),
                        'priority' => NotificationPriority::from($priority),
                        'message_hash' => hash('sha256', $message),
                        'status' => NotificationStatus::Queued,
                    ]);

                    $notificationIds[] = $notification->id;
                }

                return $batch;
            });
        } catch (Throwable $exception) {

            //перепроверка  изза дубля ли ошибка
            $batch = $this->notifications->findBatchByIdempotencyKey($idempotencyKey);

            if (!$batch) {
                throw $exception;
            }

            return $batch;
        } finally {
            if ($hasLock) {
                $this->idempotencyLock->release($idempotencyKey);
            }
        }

        foreach ($notificationIds as $notificationId) {
            $this->queue->publishNotification($notificationId, $priority);
        }

        return $batch;
    }
}
