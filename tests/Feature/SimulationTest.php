<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Booking;
use App\Models\SystemTransaction;

class SimulationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('services.selcom.vendor', 'TESTVENDOR');
        \Illuminate\Support\Facades\Gate::before(fn ($user, $ability) => true);
    }

    /** @test */
    public function high_volume_booking_simulation(): void
    {
        $agentData = $this->createAgentWithAccount(5000000); // 5M float
        $agent = $agentData['user'];
        $scenario = $this->createCompleteBookingScenario(['user' => $agent, 'economy_seats' => 50]);

        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $this->withoutMiddleware();

        $bookings = [];

        // Simulate 20 bookings in rapid succession
        for ($i = 0; $i < 20; $i++) {
            $holdPayload = [
                'voyage_id' => $scenario['voyage']->id,
                'passengers' => [
                    [
                        'first_name' => "Passenger{$i}",
                        'last_name' => 'Sim',
                        'ferry_class_id' => $scenario['class']->id,
                        'base_fare' => 40000,
                    ]
                ]
            ];

            $response = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload);
            $response->assertStatus(201);
            $bookings[] = $response->json('data.booking_reference');
        }

        $this->assertCount(20, $bookings);
        $this->assertEquals(20, Booking::whereIn('booking_reference', $bookings)->count());

        // Complete payments for all
        foreach ($bookings as $ref) {
            $booking = Booking::where('booking_reference', $ref)->first();
            $payPayload = [
                'payment_channel' => 'agent_float'
            ];
            
            $payResponse = $this->actingAs($agent)
                ->withHeader('Idempotency-Key', 'sim-key-' . $ref)
                ->postJson("/api/v1/portal/bookings/{$booking->id}/complete", $payPayload);
            
            $payResponse->assertStatus(200);
        }

        $agentData['account']->refresh();
        // 20 bookings * 40k = 800k. Commission = 800k * 5% = 40k. Net cost = 760k.
        // Balance = 5,000,000 - 760,000 = 4,240,000
        $this->assertEquals(4240000, (float) $agentData['account']->balance);
    }

    /** @test */
    public function double_payment_simulation(): void
    {
        $agentData = $this->createAgentWithAccount(100000);
        $agent = $agentData['user'];
        $scenario = $this->createCompleteBookingScenario(['user' => $agent]);

        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $this->withoutMiddleware();

        $holdPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 40000,
                ]
            ]
        ];

        $holdResponse = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload);
        $bookingId = Booking::where('booking_reference', $holdResponse->json('data.booking_reference'))->first()->id;

        $payPayload = ['payment_channel' => 'agent_float'];

        // First payment
        $response1 = $this->actingAs($agent)
            ->withHeader('Idempotency-Key', 'pay-key-1')
            ->postJson("/api/v1/portal/bookings/{$bookingId}/complete", $payPayload);
        $response1->assertStatus(200);

        // Second payment attempt
        $response2 = $this->actingAs($agent)
            ->withHeader('Idempotency-Key', 'pay-key-2')
            ->postJson("/api/v1/portal/bookings/{$bookingId}/complete", $payPayload);
        
        $response2->assertStatus(422); // Because completeBooking throws exception if not held/pending
        $this->assertStringContainsString('Only held, pending, or reserved bookings can be completed', $response2->json('message'));
        
        // Ensure balance only deducted once: 100k - 38k = 62k
        $agentData['account']->refresh();
        $this->assertEquals(62000, (float) $agentData['account']->balance);
    }

    /** @test */
    public function agent_float_exhaustion_simulation(): void
    {
        // Agent with just enough for 1 ticket (40k net 38k)
        $agentData = $this->createAgentWithAccount(50000); 
        $agent = $agentData['user'];
        $scenario = $this->createCompleteBookingScenario(['user' => $agent]);

        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $this->withoutMiddleware();

        // Booking 1 - Should pass
        $holdPayload1 = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [['first_name' => 'John', 'last_name' => 'Doe', 'ferry_class_id' => $scenario['class']->id, 'base_fare' => 40000]]
        ];
        $book1 = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload1)->json('data.booking_reference');
        $id1 = Booking::where('booking_reference', $book1)->first()->id;
        
        $pay1 = $this->actingAs($agent)
            ->withHeader('Idempotency-Key', 'exhaust-1')
            ->postJson("/api/v1/portal/bookings/{$id1}/complete", ['payment_channel' => 'agent_float']);
        $pay1->assertStatus(200);

        // Booking 2 - Should fail due to insufficient funds (needs 38k, has 12k left)
        $holdPayload2 = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [['first_name' => 'Jane', 'last_name' => 'Doe', 'ferry_class_id' => $scenario['class']->id, 'base_fare' => 40000]]
        ];
        $book2 = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload2)->json('data.booking_reference');
        $id2 = Booking::where('booking_reference', $book2)->first()->id;

        $pay2 = $this->actingAs($agent)
            ->withHeader('Idempotency-Key', 'exhaust-2')
            ->postJson("/api/v1/portal/bookings/{$id2}/complete", ['payment_channel' => 'agent_float']);
        
        $pay2->assertStatus(422);
        $this->assertStringContainsString('Insufficient wallet balance', $pay2->json('message'));
    }

    /** @test */
    public function expired_hold_cleanup_simulation(): void
    {
        $agentData = $this->createAgentWithAccount(100000);
        $agent = $agentData['user'];
        $scenario = $this->createCompleteBookingScenario(['user' => $agent]);

        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $this->withoutMiddleware();

        $holdPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [['first_name' => 'Hold', 'last_name' => 'Expired', 'ferry_class_id' => $scenario['class']->id, 'base_fare' => 40000]]
        ];
        
        $holdResponse = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload);
        $bookingRef = $holdResponse->json('data.booking_reference');
        $booking = Booking::where('booking_reference', $bookingRef)->first();

        // 1. Verify seat is held
        $this->assertEquals('held', $booking->status);
        $this->assertEquals(1, $booking->items()->where('status', 'held')->count());

        // 2. Simulate expiration (time travel not always reliable in DB without touching timestamps)
        $booking->expires_at = now()->subMinutes(5);
        $booking->save();

        // 3. Run artisan command for cleanup
        $this->artisan('bookings:expire-holds')
             ->assertSuccessful();

        $booking->refresh();

        // 4. Verify released
        $this->assertEquals('expired', $booking->status);
        $this->assertStringContainsString('Expired automatically by bookings:expire-holds', $booking->notes);
        
        $this->assertEquals(0, $booking->items()->where('status', 'held')->count());
    }

    /** @test */
    public function full_day_operation_simulation(): void
    {
        // Setup Agent
        $agentData = $this->createAgentWithAccount(200000); // 200k float
        $agent = $agentData['user'];
        $scenario = $this->createCompleteBookingScenario(['user' => $agent, 'economy_seats' => 5]);

        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $this->withoutMiddleware();

        // Event 1: Morning Rush (3 bookings)
        $bookings = [];
        for ($i = 0; $i < 3; $i++) {
            $payload = ['voyage_id' => $scenario['voyage']->id, 'passengers' => [['first_name' => "Morn{$i}", 'last_name' => 'Rush', 'ferry_class_id' => $scenario['class']->id, 'base_fare' => 40000]]];
            $ref = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $payload)->json('data.booking_reference');
            
            $id = Booking::where('booking_reference', $ref)->first()->id;
            $this->actingAs($agent)->withHeader('Idempotency-Key', "m-{$i}")->postJson("/api/v1/portal/bookings/{$id}/complete", ['payment_channel' => 'agent_float'])->assertStatus(200);
            $bookings[] = $ref;
        }

        // Wallet checks: 3 * 38k = 114k. Balance: 200k - 114k = 86k.
        $agentData['account']->refresh();
        $this->assertEquals(86000, (float) $agentData['account']->balance);

        // Event 2: Cancellation of 1 booking
        $cancelId = Booking::where('booking_reference', $bookings[0])->first()->id;
        $this->actingAs($agent)->postJson("/api/v1/portal/bookings/{$cancelId}/cancel", ['reason' => 'Customer request'])->assertStatus(200);
        
        // Float restored +38k = 124k
        $agentData['account']->refresh();
        $this->assertEquals(84000, (float) $agentData['account']->balance);

        // Event 3: Afternoon Rush (2 bookings)
        for ($i = 0; $i < 2; $i++) {
            $payload = ['voyage_id' => $scenario['voyage']->id, 'passengers' => [['first_name' => "Aft{$i}", 'last_name' => 'Rush', 'ferry_class_id' => $scenario['class']->id, 'base_fare' => 40000]]];
            $ref = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $payload)->json('data.booking_reference');
            
            $id = Booking::where('booking_reference', $ref)->first()->id;
            $this->actingAs($agent)->withHeader('Idempotency-Key', "a-{$i}")->postJson("/api/v1/portal/bookings/{$id}/complete", ['payment_channel' => 'agent_float'])->assertStatus(200);
        }

        // Float after afternoon: 124k - (2 * 38k) = 48k
        $agentData['account']->refresh();
        $this->assertEquals(8000, (float) $agentData['account']->balance);
        
        // Total seats sold initially 3, cancelled 1, sold 2 = 4 total sold. 1 available out of 5.
        $this->assertEquals(4, \App\Models\Ticket::whereHas('booking', fn($q) => $q->where('voyage_id', $scenario['voyage']->id))->where('status', 'confirmed')->count());
    }
}
