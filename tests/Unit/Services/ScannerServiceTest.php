<?php

namespace Tests\Unit\Services;

use App\Models\PortOperator;
use App\Models\User;
use App\Services\ScannerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ScannerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ScannerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ScannerService();
    }

    /** @test */
    public function it_authenticates_operator_with_valid_credentials(): void
    {
        $user = $this->createSystemUser(['domain' => 'system_user']);
        
        $port = $this->createPort();
        $operator = new PortOperator();
        $operator->operator_number = 'OP123';
        $operator->pin = Hash::make('1234');
        $operator->name = 'Scanner Operator';
        $operator->is_active = true;
        $operator->user_id = $user->id;
        $operator->port_id = $port->id;
        $operator->save();

        $result = $this->service->authenticateOperator('OP123', '1234');

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals($operator->id, $result['operator']['id']);
        $this->assertEquals('OP123', $result['operator']['operator_number']);
    }

    /** @test */
    public function it_rejects_operator_with_invalid_credentials(): void
    {
        $user = $this->createSystemUser(['domain' => 'system_user']);
        
        $port = $this->createPort();
        $operator = new PortOperator();
        $operator->operator_number = 'OP123';
        $operator->pin = Hash::make('1234');
        $operator->name = 'Scanner Operator';
        $operator->is_active = true;
        $operator->user_id = $user->id;
        $operator->port_id = $port->id;
        $operator->save();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid operator number or PIN.');

        $this->service->authenticateOperator('OP123', '9999');
    }

    /** @test */
    public function it_gets_voyage_tickets_for_manifest(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        
        $result = $this->service->getVoyageTickets($scenario['voyage']->id);

        $this->assertCount(1, $result['data']);
        $this->assertEquals($ticket->id, $result['data'][0]['ticket_id']);
        $this->assertEquals($ticket->ticket_number, $result['data'][0]['ticket_number']);
        
        $this->assertEquals(1, $result['summary']['total']);
        $this->assertEquals(0, $result['summary']['boarded']);
        $this->assertEquals(1, $result['summary']['remaining']);
    }

    /** @test */
    public function it_verifies_valid_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        
        // Setup payment status
        $booking->update(['payment_status' => 'paid']);
        $ticket = $booking->items->first();

        $result = $this->service->verifyTicket($ticket->ticket_number, $scenario['voyage']->id);

        $this->assertEquals('valid', $result['result']);
        $this->assertStringContainsString('valid and confirmed', $result['message']);
        $this->assertEquals($ticket->ticket_number, $result['ticket']['ticket_number']);
    }

    /** @test */
    public function it_rejects_verification_for_wrong_voyage_or_invalid_status(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->update(['payment_status' => 'paid']);
        $ticket = $booking->items->first();
        
        // 1. Wrong voyage
        $wrongVoyageId = $scenario['voyage']->id + 99;
        $result = $this->service->verifyTicket($ticket->ticket_number, $wrongVoyageId);
        $this->assertEquals('wrong_voyage', $result['result']);
        
        // 2. Already boarded
        $ticket->update(['status' => 'boarded']);
        $result2 = $this->service->verifyTicket($ticket->ticket_number, $scenario['voyage']->id);
        $this->assertEquals('already_boarded', $result2['result']);
        
        // 3. Cancelled
        $ticket->update(['status' => 'cancelled']);
        $result3 = $this->service->verifyTicket($ticket->ticket_number, $scenario['voyage']->id);
        $this->assertEquals('cancelled', $result3['result']);
        
        // 4. Not paid
        $ticket->update(['status' => 'confirmed']);
        $booking->update(['payment_status' => 'pending']);
        $result4 = $this->service->verifyTicket($ticket->ticket_number, $scenario['voyage']->id);
        $this->assertEquals('not_paid', $result4['result']);
    }
}
