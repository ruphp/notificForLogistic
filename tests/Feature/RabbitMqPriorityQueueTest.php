<?php

namespace Tests\Feature;

use App\Queue\RabbitMqConnectionFactory;
use App\Queue\RabbitMqNotificationConsumer;
use App\Queue\RabbitMqNotificationPublisher;
use Tests\Fakes\RecordingNotificationMessageHandler;
use Tests\TestCase;

class RabbitMqPriorityQueueTest extends TestCase
{
    private string $queueName;

    protected function setUp(): void
    {
        parent::setUp();

        $suffix = str()->uuid()->toString();
        $this->queueName = 'test.notifications.outgoing.'.$suffix;

        config([
            'rabbitmq.queue' => $this->queueName,
            'rabbitmq.max_priority' => 10,
            'rabbitmq.priorities' => [
                'high' => 10,
                'default' => 5,
                'low' => 1,
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $connection = app(RabbitMqConnectionFactory::class)->make();
        $channel = $connection->channel();

        $channel->queue_delete($this->queueName);

        $channel->close();
        $connection->close();

        parent::tearDown();
    }

    // рабит отдает сообщения из одной очереди по приоритету.
    public function test_worker_consumes_priority_queues_in_expected_order(): void
    {
        $publisher = app(RabbitMqNotificationPublisher::class);
        $consumer = app(RabbitMqNotificationConsumer::class);
        $handler = new RecordingNotificationMessageHandler();

        $publisher->publishNotification('low-message', 'low');
        $publisher->publishNotification('high-message', 'high');
        $publisher->publishNotification('default-message', 'default');

        $this->assertTrue($consumer->consumeOne($handler));
        $this->assertTrue($consumer->consumeOne($handler));
        $this->assertTrue($consumer->consumeOne($handler));

        $this->assertSame([
            'high-message',
            'default-message',
            'low-message',
        ], $handler->handled);
    }
}
