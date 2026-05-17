<?php

namespace Tests\Feature;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use App\Models\Notification;
use App\Models\NotificationBatch;
use App\Services\NotificationDeliveryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationDeliveryServiceTest extends TestCase
{
    use RefreshDatabase;

    // Happy path: успешная отправка переводит уведомление в delivered.
    public function test_successful_delivery_marks_notification_delivered(): void
    {
        $notification = $this->createNotification('ok message');

        $result = app(NotificationDeliveryService::class)->send($notification->id);

        $notification->refresh();

        $this->assertTrue($result);
        $this->assertSame(NotificationStatus::Delivered, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame('fake-'.$notification->id, $notification->provider_message_id);
        $this->assertNotNull($notification->sent_at);
        $this->assertNotNull($notification->delivered_at);
    }

    // Временная ошибка провайдера оставляет уведомление в очереди на повтор.
    public function test_temporary_error_keeps_notification_queued_for_retry(): void
    {
        $notification = $this->createNotification('fail message');

        $result = app(NotificationDeliveryService::class)->send($notification->id);

        $notification->refresh();

        $this->assertFalse($result);
        $this->assertSame(NotificationStatus::Queued, $notification->status);
        $this->assertSame(1, $notification->attempts);
        $this->assertSame('Fake provider temporary error.', $notification->last_error);
        $this->assertNull($notification->dropped_at);
    }

    // После лимита попыток уведомление уходит в dropped.
    public function test_notification_is_dropped_after_attempt_limit(): void
    {
        $notification = $this->createNotification('fail message', attempts: 2);

        $result = app(NotificationDeliveryService::class)->send($notification->id);

        $notification->refresh();

        $this->assertTrue($result);
        $this->assertSame(NotificationStatus::Dropped, $notification->status);
        $this->assertSame(3, $notification->attempts);
        $this->assertSame('Fake provider temporary error.', $notification->last_error);
        $this->assertNotNull($notification->dropped_at);
    }

    private function createNotification(string $message, int $attempts = 0): Notification
    {
        $batch = NotificationBatch::query()->create([
            'idempotency_key' => 'delivery-'.str()->uuid(),
            'channel' => NotificationChannel::Sms,
            'priority' => NotificationPriority::Default,
            'message' => $message,
            'recipient_count' => 1,
        ]);

        return Notification::query()->create([
            'batch_id' => $batch->id,
            'recipient_id' => 'driver-1',
            'channel' => NotificationChannel::Sms,
            'priority' => NotificationPriority::Default,
            'message_hash' => hash('sha256', $message),
            'status' => NotificationStatus::Queued,
            'attempts' => $attempts,
        ]);
    }
}
