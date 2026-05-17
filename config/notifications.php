<?php

return [
    'max_attempts' => (int) env('NOTIFICATION_MAX_ATTEMPTS', 3),
    'bulk_rate_limit' => [
        'max_requests' => (int) env('NOTIFICATION_BULK_RATE_LIMIT', 60),
        'seconds' => (int) env('NOTIFICATION_BULK_RATE_SECONDS', 60),
    ],
    'api_keys' => [
        env('NOTIFICATION_DEMO_API_KEY', 'local-demo-key') => 'company-demo',
    ],
];
