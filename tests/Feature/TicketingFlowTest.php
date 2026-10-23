<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\SystemConfig;

class TicketingFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_can_fetch_booking_tickets(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        // Hold and confirm booking
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user);

        // Fetch tickets via V1 API
        $response = $this->actingAs($user)->getJson("/api/v1/bookings/{$booking->booking_reference}/tickets");
        
        $response->assertStatus(200)
                 ->assertJsonPath('success', true);
                 
        $tickets = $response->json('data');
        $this->assertIsArray($tickets);
        $this->assertGreaterThan(0, count($tickets));
        $this->assertArrayHasKey('ticket_number', $tickets[0]);
    }

    #[Test]
    public function agent_can_print_booking_tickets(): void
    {
        $agentData = $this->createAgentWithAccount();
        $scenario = $this->createCompleteBookingScenario();
        
        // Agent books and confirms
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user']);
        
        // Assign the correct permission to the agent user role or directly

        $response = $this->actingAs($agentData['user'])->get("/api/v1/portal/bookings/{$booking->id}/print");
        
        $response->assertStatus(200);
        $this->assertStringContainsString($booking->booking_reference, $response->getContent());
    }
}
