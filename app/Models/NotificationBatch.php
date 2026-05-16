<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationBatch extends Model
{
    use HasUuids;

    protected $fillable = [
        'idempotency_key',
        'channel',
        'priority',
        'message',
        'recipient_count',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'priority' => NotificationPriority::class,
        'recipient_count' => 'integer',
    ];

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'batch_id');
    }
}
