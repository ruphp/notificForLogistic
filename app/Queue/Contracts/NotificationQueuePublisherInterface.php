<?php

namespace App\Queue\Contracts;

interface NotificationQueuePublisherInterface
{
    public function publishNotification(string $notificationId, string $priority): void;
}
