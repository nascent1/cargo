<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Idempotency Middleware — prevents duplicate payment/booking operations.
 *
 * Validates the `Idempotency-Key` header using the database cache table (cPanel-safe).
 * If the same key is received within the TTL window, the cached response is returned
 * instead of re-executing the operation.
 */
class IdempotencyMiddleware
{
    protected int $ttlMinutes = 60; // 1 hour TTL for idempotency keys

    public function handle(Request $request, Closure $next): Response
    {
        // Only apply to state-changing methods
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH'])) {
            return $next($request);
        }

        $idempotencyKey = $request->header('Idempotency-Key');

        if (!$idempotencyKey) {
            // BUG-014 fix: Require Idempotency-Key on state-changing requests
            // to prevent double-click duplicate submissions.
            return response()->json([
                'status' => 'error',
                'message' => 'Idempotency-Key header is required for this request.',
            ], 422);
        }

        $cacheKey = 'idempotency:' . auth()->id() . ':' . $idempotencyKey;

        // Check if we've already processed this request
        $cachedResponse = Cache::get($cacheKey);

        if ($cachedResponse !== null) {
            return response()->json(
                array_merge(
                    is_array($cachedResponse) ? $cachedResponse : ['data' => $cachedResponse],
                    ['_idempotent' => true]
                ),
                200
            );
        }

        // Process the request
        $response = $next($request);

        // Cache the response for the TTL window
        if ($response->isSuccessful()) {
            $responseData = json_decode($response->getContent(), true);
            Cache::put($cacheKey, $responseData, now()->addMinutes($this->ttlMinutes));
        }

        return $response;
    }
}
