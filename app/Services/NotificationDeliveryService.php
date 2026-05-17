<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Exceptions\NotificationDeliveryException;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Throwable;

class NotificationDeliveryService
{
    public function __construct(
        private readonly NotificationRepositoryInterface $notifications,
        private readonly FakeNotificationProvider $provider,
    ) {
    }

    public function send(string $notificationId): bool
    {
        $notification = $this->notifications->findNotificationWithBatch($notificationId);

        // Защита от повторной обработки одного и того же сообщения из RabbitMQ.
        if (!$notification || $notification->status !== NotificationStatus::Queued) {
            return true;
        }

        try {
            // Здесь пока fake-провайдер. Реальную SMS/Email-интеграцию добавили бы отдельным классом.
            $providerMessageId = $this->provider->send($notification);

            if (!$providerMessageId) {
                throw new NotificationDeliveryException('Provider did not return message id.');
            }
        } catch (Throwable $exception) {
            $error = $exception->getMessage();

            if ($notification->attempts + 1 >= config('notifications.max_attempts')) {
                $this->notifications->markNotificationDropped($notification, $error);

                return true;
            }

            $this->notifications->markNotificationAttemptFailed($notification, $error);

            return false;
        }

        $notification = $this->notifications->markNotificationSent($notification, $providerMessageId);
        $this->notifications->markNotificationDelivered($notification);

        return true;
    }
}
