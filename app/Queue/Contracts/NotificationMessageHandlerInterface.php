<?php

namespace App\Queue\Contracts;

interface NotificationMessageHandlerInterface
{
    public function handle(string $notificationId): void;
}
