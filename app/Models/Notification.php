<?php

namespace App\Models;

use App\Enums\NotificationChannel;
use App\Enums\NotificationPriority;
use App\Enums\NotificationStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    protected $fillable = [
        'batch_id',
        'recipient_id',
        'channel',
        'priority',
        'message_hash',
        'status',
        'attempts',
        'provider_message_id',
        'last_error',
        'sent_at',
        'delivered_at',
        'dropped_at',
    ];

    protected $casts = [
        'channel' => NotificationChannel::class,
        'priority' => NotificationPriority::class,
        'status' => NotificationStatus::class,
        'attempts' => 'integer',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'dropped_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(NotificationBatch::class, 'batch_id');
    }
}
