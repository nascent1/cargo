<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Ticket;
use App\Services\BoardingService;
use App\Services\ScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BoardingService $boardingService;
    protected ScannerService $scannerService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->boardingService = app(BoardingService::class);
        $this->scannerService = app(ScannerService::class);
    }

    /** @test */
    public function it_successfully_boards_a_valid_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);
        $ticket = $booking->items->first();

        $this->assertNotNull($ticket->ticket_number);

        $scanResult = $this->scannerService->verifyTicket($ticket->ticket_number, $scenario['voyage']->id);
        $this->assertEquals('valid', $scanResult['result']);

        $portId = $scenario['port1']->id;
        $this->boardingService->processScan($ticket->id, $scenario['voyage']->id, null, $portId);

        $ticket->refresh();
        $this->assertEquals('boarded', $ticket->status);
        $this->assertDatabaseHas('boarding_events', [
            'ticket_id' => $ticket->id,
            'port_id' => $portId,
            'result' => 'success',
        ]);
    }

    /** @test */
    public function it_prevents_boarding_an_already_boarded_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);
        $ticket = $booking->items->first();
        
        $portId = $scenario['port1']->id;
        
        $this->boardingService->processScan($ticket->id, $scenario['voyage']->id, null, $portId);
        
        $event = $this->boardingService->processScan($ticket->id, $scenario['voyage']->id, null, $portId);
        $this->assertEquals('duplicate', $event->result);
    }

    /** @test */
    public function it_fails_boarding_if_wrong_voyage(): void
    {
        $scenario1 = $this->createCompleteBookingScenario();
        
        $voyage2 = $this->createVoyage($scenario1['route'], $scenario1['ferry'], [
            'departure_time' => '14:00:00'
        ]);

        $user = $scenario1['user'];
        $booking = $this->createConfirmedBooking($scenario1['voyage'], $scenario1['class'], $user, 1);
        
        $ticket = $booking->items->first();
        $ticket->update(['voyage_id' => $voyage2->id]);

        $portId = $scenario1['port1']->id;

        $event = $this->boardingService->processScan($ticket->id, $voyage2->id, null, $portId);
        
        $ticket->refresh();
        $this->assertEquals('boarded', $ticket->status);
    }
}
