<?php

namespace App\Services;

use Illuminate\Support\Facades\Redis;

class RedisIdempotencyLock
{
    public function tryLock(string $key): bool
    {
        return (bool) Redis::set($this->key($key), '1', 'EX', 30, 'NX');
    }

    public function release(string $key): void
    {
        Redis::del($this->key($key));
    }

    private function key(string $key): string
    {
        return 'notification:idempotency:'.$key;
    }
}
