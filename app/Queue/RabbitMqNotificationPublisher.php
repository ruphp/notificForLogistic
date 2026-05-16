<?php

namespace App\Queue;

use App\Queue\Contracts\NotificationQueuePublisherInterface;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMqNotificationPublisher implements NotificationQueuePublisherInterface
{
    public function __construct(private readonly RabbitMqConnectionFactory $connections)
    {
    }

    public function publishNotification(string $notificationId): void
    {
        $connection = $this->connections->make();
        $channel = $connection->channel();
        $queueName = config('rabbitmq.queue');

        $channel->queue_declare($queueName, false, true, false, false);

        $message = new AMQPMessage(
            json_encode([
                'notification_id' => $notificationId,
                'created_at' => now()->toISOString(),
            ], JSON_THROW_ON_ERROR),
            [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            ],
        );

        $channel->basic_publish($message, '', $queueName);

        $channel->close();
        $connection->close();
    }
}
