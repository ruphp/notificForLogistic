<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('about:service', function (): void {
    $this->info('Notification Service is ready.');
});
