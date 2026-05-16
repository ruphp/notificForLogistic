<?php

namespace App\Services;

use App\Enums\NotificationStatus;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationDeliveryService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications)
    {
    }

    public function send(string $notificationId): void
    {
        $notification = $this->notifications->findNotificationWithBatch($notificationId);

        //Защита от повторной обработки одного и того же сообщения из рабита
        if (!$notification || $notification->status !== NotificationStatus::Queued) {
            return;
        }

        // Здесь пока имитация провайдера. Реальную cvc / email интеграцию добавили бы отдельным классом
        $providerMessageId = 'fake-'.$notification->id;

        $notification = $this->notifications->markNotificationSent($notification, $providerMessageId);
        $this->notifications->markNotificationDelivered($notification);
    }
}
