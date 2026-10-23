<?php

namespace Tests\Unit\Services;

use App\Models\BoardingEvent;
use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Voyage;
use App\Services\BoardingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BoardingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BoardingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BoardingService();
    }

    #[Test]
    public function it_processes_successful_scan_and_updates_ticket_and_booking_status(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $qrData = $booking->booking_reference . ':ITEM-' . $ticket->id;
        $event = $this->service->processScan($qrData, $scenario['voyage']->id);

        $this->assertEquals('success', $event->result);
        $this->assertEquals($ticket->id, $event->ticket_id);
        
        $ticket->refresh();
        $booking->refresh();
        
        $this->assertEquals('boarded', $ticket->status);
        $this->assertEquals('boarded', $booking->status);
    }

    #[Test]
    public function it_rejects_invalid_qr_data(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $event = $this->service->processScan('INVALID_QR', $scenario['voyage']->id);

        $this->assertEquals('invalid', $event->result);
        $this->assertNull($event->ticket_id);
        $this->assertStringContainsString('Could not parse QR data', $event->notes);
    }

    #[Test]
    public function it_rejects_unconfirmed_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->update(['status' => 'cancelled']); // not confirmed
        
        $ticket = $booking->items->first();
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $qrData = $ticket->id; // Fallback parsing supports just ID
        $event = $this->service->processScan((string) $qrData, $scenario['voyage']->id);

        $this->assertEquals('invalid', $event->result);
        $this->assertStringContainsString('Booking status is \'cancelled\'', $event->notes);
        
        $ticket->refresh();
        $this->assertNotEquals('boarded', $ticket->status);
    }

    #[Test]
    public function it_rejects_ticket_for_wrong_voyage(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        
        $wrongVoyage = $this->createVoyage($scenario['route'], $scenario['voyage']->ferry, [
            'departure_date' => now()->addDays(5)->format('Y-m-d'),
            'schedule_id' => $scenario['voyage']->schedule_id
        ]);
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $qrData = $booking->booking_reference . ':ITEM-' . $ticket->id;
        $event = $this->service->processScan($qrData, $wrongVoyage->id);

        $this->assertEquals('wrong_voyage', $event->result);
    }

    #[Test]
    public function it_rejects_duplicate_scan(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $qrData = $booking->booking_reference . ':ITEM-' . $ticket->id;
        
        // First scan
        $event1 = $this->service->processScan($qrData, $scenario['voyage']->id);
        $this->assertEquals('success', $event1->result);

        // Second scan
        $event2 = $this->service->processScan($qrData, $scenario['voyage']->id);
        $this->assertEquals('duplicate', $event2->result);
    }

    #[Test]
    public function it_rejects_scan_after_voyage_departure(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        // Past voyage
        $voyage = $this->createVoyage($scenario['route'], $scenario['voyage']->ferry, [
            'departure_date' => now()->subDays(1)->format('Y-m-d'),
            'departure_time' => '10:00:00',
            'schedule_id' => $scenario['voyage']->schedule_id
        ]);
        
        $booking = $this->createConfirmedBooking($voyage, $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        
        $actor = $this->createSystemUser();
        $this->actingAs($actor);

        $qrData = $booking->booking_reference . ':ITEM-' . $ticket->id;
        $event = $this->service->processScan($qrData, $voyage->id);

        $this->assertEquals('expired', $event->result);
    }
}
