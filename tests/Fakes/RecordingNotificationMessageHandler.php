<?php

namespace Tests\Fakes;

use App\Queue\Contracts\NotificationMessageHandlerInterface;

// Фейковый handler для теста приоритетов: не доставляет уведомления, а только запоминает порядок сообщений от RabbitMQ.
class RecordingNotificationMessageHandler implements NotificationMessageHandlerInterface
{
    public array $handled = [];

    public function handle(string $notificationId): bool
    {
        $this->handled[] = $notificationId;

        return true;
    }
}
