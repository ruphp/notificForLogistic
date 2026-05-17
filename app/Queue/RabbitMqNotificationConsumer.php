<?php

namespace App\Queue;

use App\Queue\Contracts\NotificationMessageHandlerInterface;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
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

        $this->declareQueue($channel, $queueName);
        $channel->basic_qos(null, 1, null);

        $channel->basic_consume(
            $queueName,
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($handler): void {
                $this->handleMessage($message, $handler);
            },
        );

        while ($channel->is_consuming()) {
            $channel->wait();
        }
    }

    // Обрабатывает одно сообщение и завершает работу. Удобно для отладки и интеграционных проверок.
    public function consumeOne(NotificationMessageHandlerInterface $handler): bool
    {
        $connection = $this->connections->make();
        $channel = $connection->channel();
        $queueName = config('rabbitmq.queue');

        $this->declareQueue($channel, $queueName);
        $channel->basic_qos(null, 1, null);

        // basic_get забирает одно сообщение и сразу возвращает управление.
        // Для обычного worker-процесса используется consume() с basic_consume + wait().
        $message = $channel->basic_get($queueName);
        $isConsumed = $message instanceof AMQPMessage;

        if ($isConsumed) {
            $this->handleMessage($message, $handler);
        }

        $channel->close();
        $connection->close();

        return $isConsumed;
    }

    private function declareQueue(AMQPChannel $channel, string $queueName): void
    {
        $channel->queue_declare(
            $queueName,
            false,
            true,
            false,
            false,
            false,
            new AMQPTable(['x-max-priority' => config('rabbitmq.max_priority')]),
        );
    }

    private function handleMessage(AMQPMessage $message, NotificationMessageHandlerInterface $handler): void
    {
        try {
            $payload = json_decode($message->getBody(), true, 512, JSON_THROW_ON_ERROR);
            $isFinished = $handler->handle((string) $payload['notification_id']);

            $isFinished ? $message->ack() : $message->nack(true, false);
        } catch (Throwable) {
            // Если упали до фиксации ошибки в БД, возвращаем сообщение в очередь.
            $message->nack(true, false);
        }
    }
}
