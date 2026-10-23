<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\AgentAccount;
use App\Gateways\Payment\SelcomGateway;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SystemTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function end_to_end_agent_booking_and_boarding_journey(): void
    {
        // 1. Setup Data
        \Illuminate\Support\Facades\Config::set('services.selcom.base_url', 'https://apigw.selcommobile.com');
        \Illuminate\Support\Facades\Config::set('services.selcom.api_key', 'test_key');
        \Illuminate\Support\Facades\Config::set('services.selcom.api_secret', 'test_secret');
        \Illuminate\Support\Facades\Config::set('services.selcom.vendor', 'TESTVENDOR');
        \Illuminate\Support\Facades\Gate::before(fn ($user, $ability) => true);

        $agentData = $this->createAgentWithAccount(100000); // 100,000 float
        $agent = $agentData['user'];
        
        $scenario = $this->createCompleteBookingScenario(['user' => $agent]);
        
        $agent->agent->ferries()->attach($scenario['ferry']->id);
        $agent->agent->routes()->attach($scenario['route']->id);

        $scannerUser = $this->createSystemUser(['domain' => 'system_user']);

        // 2. Search Voyages
        $date = $scenario['voyage']->departure_date;
        $searchResponse = $this->actingAs($agent)->getJson('/api/v1/portal/voyages/search?route_id=' . $scenario['route']->id . '&departure_date=' . $date);
        $searchResponse->assertStatus(200);

        // 3. Hold Ticket
        $holdPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'first_name' => 'E2E',
                    'last_name' => 'Passenger',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 40000,
                ]
            ]
        ];

        $holdResponse = $this->actingAs($agent)->postJson('/api/v1/bookings/hold', $holdPayload);
        $holdResponse->assertStatus(201);
        $bookingRef = $holdResponse->json('data.booking_reference');
        $this->assertNotEmpty($bookingRef);

        // 4. Pay using Agent Float
        $booking = \App\Models\Booking::where('booking_reference', $bookingRef)->first();
        $bookingId = $booking->id;
        
        $payPayload = [
            'payment_channel' => 'agent_float'
        ];

        $this->withoutMiddleware([\App\Http\Middleware\CheckPermission::class]);
        $payResponse = $this->actingAs($agent)->withHeader('Idempotency-Key', 'test-key-123')->postJson("/api/v1/portal/bookings/{$bookingId}/complete", $payPayload);
        $payResponse->assertStatus(200)
                    ->assertJsonPath('status', 'success');

        // Verify float deduction (with 5% commission: 100,000 - 40,000 + 2,000 = 62,000)
        $agentData['account']->refresh();
        $this->assertEquals(62000, (float) $agentData['account']->balance);

        // 5. Fetch Ticket details
        $printResponse = $this->actingAs($agent)->get("/api/v1/portal/bookings/{$bookingId}/print");
        $printResponse->assertStatus(200);

        // 6. Board Ticket
        $ticket = \App\Models\Ticket::whereHas('booking', fn($q) => $q->where('booking_reference', $bookingRef))->first();
        
        // Scanner logs in and verifies
        $verifyPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'ticket_number' => $ticket->ticket_number,
            'scanner_device_id' => 'DEV-E2E'
        ];

        $verifyResponse = $this->actingAs($scannerUser)->postJson('/api/v1/scanner/verify', $verifyPayload);
        $verifyResponse->assertStatus(200)
                       ->assertJsonPath('result', 'valid');

        // Board
        $boardPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'ticket_number' => $ticket->ticket_number,
            'scanner_device_id' => 'DEV-E2E',
            'override_validation' => false
        ];

        $boardResponse = $this->actingAs($scannerUser)->postJson('/api/v1/scanner/board', $boardPayload);
        $boardResponse->assertStatus(200)
                      ->assertJsonPath('result', 'success');

        // Final Asserts
        $ticket->refresh();
        $this->assertEquals('boarded', $ticket->status);
        $this->assertEquals('boarded', $ticket->booking->status);
    }
}
