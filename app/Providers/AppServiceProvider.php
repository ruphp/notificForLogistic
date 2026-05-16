<?php

namespace App\Providers;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\EloquentNotificationRepository;
use App\Queue\Contracts\NotificationQueuePublisherInterface;
use App\Queue\RabbitMqNotificationPublisher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class,
        );

        $this->app->bind(
            NotificationQueuePublisherInterface::class,
            RabbitMqNotificationPublisher::class,
        );
    }
}
