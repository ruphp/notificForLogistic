<?php

namespace App\Queue;

use App\Queue\Contracts\NotificationMessageHandlerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Throwable;

class RabbitMqNotificationConsumer
{
    public function __construct(private readonly RabbitMqConnectionFactory $connections)
    {
    }

    public function consume(NotificationMessageHandlerInterface $handler): void
    {
        $connection = $this->connections->make();
        $channel = $connection->channel();
        $queueName = config('rabbitmq.queue');

        $channel->queue_declare($queueName, false, true, false, false);
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($handler): void {
                try {
                    $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
                    $isFinished = $handler->handle((string) $payload['notification_id']);

                    $isFinished ? $message->ack() : $message->nack(true, false);
                } catch (Throwable) {
                    // Если упали до фиксации ошибки в БД, возвращаем сообщение в очередь.
                    $message->nack(true, false);
                }
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }
}
