<?php
/**
 * фейковый провайдер - проверим retry при   плохом поведении провайдера без настоящего сервиса
 */
namespace App\Services;

use App\Exceptions\NotificationDeliveryException;
use App\Models\Notification;
use Illuminate\Support\Str;

class FakeNotificationProvider
{
    public function send(Notification $notification): string
    {
        if (Str::contains((string) $notification->batch?->message, 'fail')) {
            throw new NotificationDeliveryException('Fake provider temporary error.');
        }

        return 'fake-'.$notification->id;
    }
}
