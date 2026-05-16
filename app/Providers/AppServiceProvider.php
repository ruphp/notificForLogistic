<?php

namespace App\Providers;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\EloquentNotificationRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            NotificationRepositoryInterface::class,
            EloquentNotificationRepository::class,
        );
    }
}
