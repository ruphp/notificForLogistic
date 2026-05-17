<?php

namespace Tests\Fakes;

use App\Queue\Contracts\NotificationQueuePublisherInterface;

class FakeNotificationQueuePublisher implements NotificationQueuePublisherInterface
{
    /**
     * @var array<int, string>
     */
    public array $published = [];

    public function publishNotification(string $notificationId): void
    {
        $this->published[] = $notificationId;
    }
}
