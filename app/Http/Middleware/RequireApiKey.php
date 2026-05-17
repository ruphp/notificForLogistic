<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RequireApiKey
{
    public function handle(Request $request, Closure $next): JsonResponse
    {
        $apiKey = (string) $request->header('X-Api-Key');
        $clientId = config("notifications.api_keys.$apiKey");

        if (!$clientId) {
            return response()->json([
                'message' => 'Invalid API key.',
            ], 401);
        }

        $request->attributes->set('client_id', $clientId);

        return $next($request);
    }
}
