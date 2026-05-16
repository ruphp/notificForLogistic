<?php

use App\Queue\RabbitMqNotificationConsumer;
use App\Queue\NotificationDeliveryMessageHandler;
use Illuminate\Support\Facades\Artisan;

Artisan::command('about:service', function (): void {
    $this->info('Notification Service is ready.');
});

Artisan::command('notifications:consume', function (
    RabbitMqNotificationConsumer $consumer,
    NotificationDeliveryMessageHandler $handler,
): void {
    $this->info('Waiting notifications from RabbitMQ...');

    $consumer->consume($handler);
});
