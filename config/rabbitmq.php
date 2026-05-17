<?php

return [
    'host' => env('RABBITMQ_HOST', 'rabbitmq'),
    'port' => (int) env('RABBITMQ_PORT', 5672),
    'user' => env('RABBITMQ_USER', 'guest'),
    'password' => env('RABBITMQ_PASSWORD', 'guest'),
    'vhost' => env('RABBITMQ_VHOST', '/'),
    'queue' => env('RABBITMQ_QUEUE', 'notifications.outgoing'),
    'max_priority' => (int) env('RABBITMQ_MAX_PRIORITY', 10),
    'priorities' => [
        'high' => 10,
        'default' => 5,
        'low' => 1,
    ],
];
