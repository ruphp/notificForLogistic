<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBulkNotificationRequest;
use App\Services\BulkNotificationService;
use App\Services\NotificationHistoryService;
use App\Services\RedisBulkRequestLimiter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController
{
    public function store(
        StoreBulkNotificationRequest $request,
        BulkNotificationService $service,
        RedisBulkRequestLimiter $limiter,
    ): JsonResponse
    {
        $data = $request->validated();
        $clientId = (string) $request->attributes->get('client_id');

        if (!$limiter->allow('bulk:'.$clientId)) {
            return response()->json([
                'message' => 'Вы превысили лимит обращений.',
            ], 429);
        }

        $serviceIdempotencyKey = $clientId.':'.$data['idempotency_key'];

        $batch = $service->create(
            idempotencyKey: $serviceIdempotencyKey,
            channel: $data['channel'],
            priority: $data['priority'],
            message: $data['message'],
            recipientIds: array_values(array_unique($data['recipient_ids'])),
        );

        return response()->json([
            'batch_id' => $batch->id,
            'idempotency_key' => $data['idempotency_key'],
            'status' => 'accepted',
            'recipient_count' => $batch->recipient_count,
        ], $batch->wasRecentlyCreated ? 202 : 200);
    }

    public function bySubscriber(
        Request $request,
        string $subscriberId,
        NotificationHistoryService $service,
    ): JsonResponse
    {
        $limit = min((int) $request->query('limit', 50), 100);

        return response()->json([
            'subscriber_id' => $subscriberId,
            'items' => $service->bySubscriber($subscriberId, $limit),
        ]);
    }
}
