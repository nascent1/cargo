<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\IdempotencyMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class IdempotencyMiddlewareTest extends TestCase
{
    protected IdempotencyMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new IdempotencyMiddleware();
    }

    #[Test]
    public function it_rejects_post_requests_without_idempotency_key(): void
    {
        $request = Request::create('/api/v1/bookings', 'POST', ['voyage_id' => 1]);

        $response = $this->middleware->handle($request, function () {
            return response()->json(['status' => 'ok']);
        });

        // BUG-014: Must reject, not silently pass through
        $this->assertEquals(422, $response->getStatusCode());
        $data = json_decode($response->getContent(), true);
        $this->assertStringContainsString('Idempotency-Key', $data['message']);
    }

    #[Test]
    public function it_allows_get_requests_without_idempotency_key(): void
    {
        $request = Request::create('/api/v1/bookings', 'GET');

        $response = $this->middleware->handle($request, function () {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    #[Test]
    public function it_allows_post_requests_with_idempotency_key(): void
    {
        $request = Request::create('/api/v1/bookings', 'POST', ['voyage_id' => 1]);
        $request->headers->set('Idempotency-Key', 'unique-key-' . uniqid());

        $response = $this->middleware->handle($request, function () {
            return response()->json(['status' => 'created'], 201);
        });

        $this->assertContains($response->getStatusCode(), [200, 201]);
    }

    #[Test]
    public function it_returns_cached_response_for_duplicate_key(): void
    {
        $idempotencyKey = 'test-key-' . uniqid();
        $cacheKey = 'idempotency::' . $idempotencyKey;

        // Simulate a previously cached response
        Cache::put($cacheKey, ['booking_id' => 42, 'status' => 'confirmed'], now()->addMinutes(60));

        $request = Request::create('/api/v1/bookings', 'POST', ['voyage_id' => 1]);
        $request->headers->set('Idempotency-Key', $idempotencyKey);

        $response = $this->middleware->handle($request, function () {
            // This should NOT be called for a duplicate key
            return response()->json(['status' => 'should_not_reach'], 201);
        });

        $data = json_decode($response->getContent(), true);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($data['_idempotent'] ?? false);
    }

    #[Test]
    public function it_rejects_put_requests_without_idempotency_key(): void
    {
        $request = Request::create('/api/v1/bookings/1', 'PUT', ['status' => 'cancelled']);

        $response = $this->middleware->handle($request, function () {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(422, $response->getStatusCode());
    }

    #[Test]
    public function it_rejects_patch_requests_without_idempotency_key(): void
    {
        $request = Request::create('/api/v1/bookings/1', 'PATCH', ['seat_number' => 'A1']);

        $response = $this->middleware->handle($request, function () {
            return response()->json(['status' => 'ok']);
        });

        $this->assertEquals(422, $response->getStatusCode());
    }
}
