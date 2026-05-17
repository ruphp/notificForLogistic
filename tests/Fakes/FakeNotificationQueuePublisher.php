<?php

namespace Tests\Fakes;

use App\Queue\Contracts\NotificationQueuePublisherInterface;

class FakeNotificationQueuePublisher implements NotificationQueuePublisherInterface
{
    public array $published = [];

    public function publishNotification(string $notificationId, string $priority): void
    {
        $this->published[] = [
            'id' => $notificationId,
            'priority' => $priority,
        ];
    }
}
