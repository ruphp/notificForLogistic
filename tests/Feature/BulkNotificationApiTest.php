<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Queue\Contracts\NotificationQueuePublisherInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\FakeNotificationQueuePublisher;
use Tests\TestCase;

class BulkNotificationApiTest extends TestCase
{
    use RefreshDatabase;

    // Happy path: апи создает пачку, уведомления и задачи для очереди.
    public function test_bulk_request_creates_batch_notifications_and_queue_messages(): void
    {
        $publisher = new FakeNotificationQueuePublisher();

        $this->app->instance(NotificationQueuePublisherInterface::class, $publisher);

        $response = $this->postJson('/api/notifications/bulk', [
            'idempotency_key' => 'api-test-1',
            'channel' => 'sms',
            'priority' => 'high',
            'message' => 'Test message',
            'recipient_ids' => ['driver-1', 'driver-2'],
        ], [
            'X-Api-Key' => 'local-demo-key',
        ]);

        $response
            ->assertAccepted()
            ->assertJsonPath('idempotency_key', 'api-test-1')
            ->assertJsonPath('recipient_count', 2);

        $this->assertDatabaseHas('notification_batches', [
            'idempotency_key' => 'api-test-1',
            'recipient_count' => 2,
        ]);

        $this->assertDatabaseCount('notifications', 2);
        $this->assertCount(2, $publisher->published);
    }

    // Повторный запрос с тем же ключом не должен создавать дубли.
    public function test_repeated_idempotency_key_does_not_create_duplicates(): void
    {
        $publisher = new FakeNotificationQueuePublisher();

        $this->app->instance(NotificationQueuePublisherInterface::class, $publisher);

        $payload = [
            'idempotency_key' => 'api-test-duplicate',
            'channel' => 'sms',
            'priority' => 'default',
            'message' => 'Same message',
            'recipient_ids' => ['driver-1', 'driver-2'],
        ];

        $headers = ['X-Api-Key' => 'local-demo-key'];

        $this->postJson('/api/notifications/bulk', $payload, $headers)->assertAccepted();
        $this->postJson('/api/notifications/bulk', $payload, $headers)->assertOk();

        $this->assertDatabaseCount('notification_batches', 1);
        $this->assertDatabaseCount('notifications', 2);
        $this->assertCount(2, $publisher->published);
    }

    // Redis ограничивает слишком частые запросы на создание пачек.
    public function test_bulk_request_rate_limit_returns_429(): void
    {
        config(['notifications.bulk_rate_limit.max_requests' => 1]);
        config(['notifications.bulk_rate_limit.seconds' => 60]);

        $publisher = new FakeNotificationQueuePublisher();
        $apiKey = 'limit-key-'.random_int(1, 100000);
        config(["notifications.api_keys.$apiKey" => 'company-limit']);

        $this->app->instance(NotificationQueuePublisherInterface::class, $publisher);

        $this
            ->withHeader('X-Api-Key', $apiKey)
            ->postJson('/api/notifications/bulk', [
                'idempotency_key' => 'api-test-limit-1',
                'channel' => 'sms',
                'priority' => 'default',
                'message' => 'Limit message',
                'recipient_ids' => ['driver-1'],
            ])
            ->assertAccepted();

        $this
            ->withHeader('X-Api-Key', $apiKey)
            ->postJson('/api/notifications/bulk', [
                'idempotency_key' => 'api-test-limit-2',
                'channel' => 'sms',
                'priority' => 'default',
                'message' => 'Limit message',
                'recipient_ids' => ['driver-2'],
            ])
            ->assertStatus(429);
    }

    // Рабочие методы API требуют ключ клиента.
    public function test_api_key_is_required(): void
    {
        $this
            ->postJson('/api/notifications/bulk', [
                'idempotency_key' => 'api-test-no-client',
                'channel' => 'sms',
                'priority' => 'default',
                'message' => 'No client',
                'recipient_ids' => ['driver-1'],
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Invalid API key.');
    }

    // История получателя должна возвращать созданные уведомления.
    public function test_subscriber_history_returns_notifications(): void
    {
        $publisher = new FakeNotificationQueuePublisher();

        $this->app->instance(NotificationQueuePublisherInterface::class, $publisher);

        $this->postJson('/api/notifications/bulk', [
            'idempotency_key' => 'api-test-history',
            'channel' => 'email',
            'priority' => 'low',
            'message' => 'History message',
            'recipient_ids' => ['subscriber-1'],
        ], [
            'X-Api-Key' => 'local-demo-key',
        ])->assertAccepted();

        $response = $this->getJson('/api/subscribers/subscriber-1/notifications', [
            'X-Api-Key' => 'local-demo-key',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('subscriber_id', 'subscriber-1')
            ->assertJsonPath('items.0.channel', 'email')
            ->assertJsonPath('items.0.priority', 'low')
            ->assertJsonPath('items.0.status', 'queued')
            ->assertJsonPath('items.0.message', 'History message');
    }
}
