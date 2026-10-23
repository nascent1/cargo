<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use App\Models\SystemConfig;

class BoardingFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function scanner_can_verify_and_board_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        // Hold and confirm booking
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user);
        $ticket = $booking->items->first();
        
        $scannerUser = $this->createSystemUser(['domain' => 'system_user']);
        
        // Login as scanner is not fully needed if we just actAs
        
        // 1. Verify Ticket
        $verifyPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'ticket_number' => $ticket->ticket_number,
            'scanner_device_id' => 'DEV001'
        ];

        $verifyResponse = $this->actingAs($scannerUser)->postJson('/api/v1/scanner/verify', $verifyPayload);
        
        $verifyResponse->assertStatus(200)
                       ->assertJsonPath('result', 'valid')
                       ->assertJsonPath('ticket.ticket_number', $ticket->ticket_number);

        // 2. Board Ticket
        $boardPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'ticket_number' => $ticket->ticket_number,
            'scanner_device_id' => 'DEV001',
            'override_validation' => false
        ];

        $boardResponse = $this->actingAs($scannerUser)->postJson('/api/v1/scanner/board', $boardPayload);
        
        $boardResponse->assertStatus(200)
                      ->assertJsonPath('result', 'success')
                      ->assertJsonPath('message', 'Passenger boarded successfully.');
                      
        $ticket->refresh();
        $this->assertEquals('boarded', $ticket->status);
    }
}
