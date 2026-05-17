<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisBulkRequestLimiter
{
    public function allow(string $key): bool
    {
        $redisKey = 'notification:limit:'.$key;
        $count = (int) Redis::incr($redisKey);

        if ($count === 1) {
            Redis::expire($redisKey, config('notifications.bulk_rate_limit.seconds'));
        }

        return $count <= config('notifications.bulk_rate_limit.max_requests');
    }
}
