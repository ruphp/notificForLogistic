<?php

use Illuminate\Support\Facades\Route;

Route::get('/', fn () => response()->json([
    'service' => 'Notification Service',
    'status' => 'ok',
    'endpoints' => [
        'health' => '/up',
        'api_index' => '/api',
    ],
]));
