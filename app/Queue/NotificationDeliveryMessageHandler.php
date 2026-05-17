<?php

namespace App\Queue;

use App\Queue\Contracts\NotificationMessageHandlerInterface;
use App\Services\NotificationDeliveryService;

class NotificationDeliveryMessageHandler implements NotificationMessageHandlerInterface
{
    public function __construct(private readonly NotificationDeliveryService $delivery)
    {
    }

    public function handle(string $notificationId): bool
    {
        return $this->delivery->send($notificationId);
    }
}
