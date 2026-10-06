<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\FerryClass;
use App\Models\SystemConfig;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Voyage;
use App\Models\AgentAccount;
use App\Models\DualControlRequest;
use App\Services\DualControlService;
use App\Services\TicketManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Permission;
use Tests\TestCase;

class TicketManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TicketManagementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $dualControlService = app(DualControlService::class);
        $this->service = new TicketManagementService($dualControlService);
        
        SystemConfig::setValue('cancellation_penalty_percentage', '10');
    }

    /** @test */
    public function it_allows_editing_booking_notes_without_penalty(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        
        $actor = $this->createSystemUser();

        $updatedBooking = $this->service->editBooking($booking, ['notes' => 'Test Note'], $actor);

        $this->assertEquals('Test Note', $updatedBooking->notes);
        $this->assertEquals(0, $updatedBooking->edit_count ?? 0); // No items changed, edit_count remains unchanged
    }

    /** @test */
    public function it_blocks_editing_more_than_once_without_permission(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking->update(['edit_count' => 1]);
        
        $actor = $this->createSystemUser(); // No permissions by default

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Booking has already been edited once');

        $this->service->editBooking($booking, ['notes' => 'Second Edit'], $actor);
    }

    /** @test */
    public function it_requires_dual_control_for_class_downgrade(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        $vipClass = $this->createFerryClass($scenario['voyage']->ferry, ['name' => 'VIP', 'sort_order' => 1, 'is_active' => true]);
        $economyClass = $this->createFerryClass($scenario['voyage']->ferry, ['name' => 'Economy', 'sort_order' => 3, 'is_active' => true]);
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $vipClass, $scenario['user']);
        $ticket = $booking->items->first();
        
        $actor = $this->createSystemUser();
        // Give permission to downgrade, but mock dual control to require it
        Permission::firstOrCreate(['name' => 'ticket:downgrade_class'], ['display_name' => 'Downgrade Ticket Class', 'module' => 'booking']);
        $actor->grantPermission('ticket:downgrade_class');
        $this->actingAs($actor);
        
        SystemConfig::setValue('dual_control_class_downgrade', 'true'); // Simulate require

        try {
            $this->service->editBooking($booking, [
                'items' => [
                    $ticket->id => [
                        'ferry_class_id' => $economyClass->id
                    ]
                ]
            ], $actor);
            $this->fail('Expected Exception was not thrown');
        } catch (\Exception $e) {
            if ($e instanceof \PHPUnit\Framework\AssertionFailedError) {
                throw $e;
            }
            $this->assertStringContainsString('DUAL_CONTROL_PENDING', $e->getMessage());
        }
        
        // Note: DualControlRequest is rolled back by the DB transaction inside editBooking
        // Therefore, we only assert the exception was thrown.
    }

    /** @test */
    public function it_allows_class_upgrade_and_updates_fares(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        $economyClass = $this->createFerryClass($scenario['voyage']->ferry, ['name' => 'Economy', 'sort_order' => 3, 'is_active' => true]);
        $vipClass = $this->createFerryClass($scenario['voyage']->ferry, ['name' => 'VIP', 'sort_order' => 1, 'is_active' => true]);
        
        // Setup fares
        \App\Models\Fare::create([
            'route_id' => $scenario['route']->id,
            'ferry_class_id' => $economyClass->id,
            'passenger_type_id' => 1,
            'adult_price' => 35000,
            'child_price' => 20000,
            'status' => 'active'
        ]);
        
        \App\Models\Fare::create([
            'route_id' => $scenario['route']->id,
            'ferry_class_id' => $vipClass->id,
            'passenger_type_id' => 1,
            'adult_price' => 50000,
            'child_price' => 30000,
            'status' => 'active'
        ]);
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $economyClass, $scenario['user']);
        $ticket = $booking->items->first();
        $ticket->update(['base_fare' => 35000, 'final_fare' => 35000]);
        $booking->update(['total_amount' => 35000, 'net_amount' => 35000]);
        
        $actor = $this->createSystemUser();

        $updatedBooking = $this->service->editBooking($booking, [
            'items' => [
                $ticket->id => [
                    'ferry_class_id' => $vipClass->id
                ]
            ]
        ], $actor);

        $updatedTicket = $updatedBooking->items->first();
        
        $this->assertEquals($vipClass->id, $updatedTicket->ferry_class_id);
        $this->assertEquals(50000.0, $updatedTicket->base_fare);
        $this->assertEquals(50000.0, $updatedBooking->total_amount);
        $this->assertEquals(1, $updatedBooking->edit_count);
    }

    /** @test */
    public function it_reschedules_ticket_for_free_on_first_edit(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        $ticket->update(['edit_count' => 0]);
        
        // Same route, same class, different date/time (different voyage)
        $newVoyage = $this->createVoyage($scenario['route'], $scenario['voyage']->ferry, [
            'departure_date' => now()->addDays(2)->format('Y-m-d'),
            'departure_time' => '10:00:00',
            'status' => 'scheduled',
            'schedule_id' => $scenario['voyage']->schedule_id,
        ]);
        
        $actor = $this->createSystemUser();
        
        $updatedTicket = $this->service->rescheduleTicket($ticket, $newVoyage->id, $scenario['class']->id, $actor);

        // Check it was rescheduled (new booking created because of split logic for date change)
        $this->assertNotEquals($booking->id, $updatedTicket->booking_id);
        $this->assertEquals($newVoyage->id, $updatedTicket->voyage_id);
        
        // Assert NO penalty transactions were created for this user/agent
        $this->assertDatabaseMissing('transactions', [
            'transaction_number' => ['LIKE', 'RSCH-PEN-%']
        ]);
    }

    /** @test */
    public function it_charges_reschedule_penalty_on_subsequent_edits(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        
        // Create agent user who owns the booking to test penalty deduction
        $agentData = $this->createAgentWithAccount(100000);
        $agentUser = $agentData['user'];
        $agentAccount = $agentData['account'];
        
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentUser);
        $ticket = $booking->items->first();
        $ticket->update(['edit_count' => 1, 'base_fare' => 50000, 'final_fare' => 50000]);
        
        $newVoyage = $this->createVoyage($scenario['route'], $scenario['voyage']->ferry, [
            'departure_date' => now()->addDays(2)->format('Y-m-d'),
            'departure_time' => '10:00:00',
            'status' => 'scheduled',
            'schedule_id' => $scenario['voyage']->schedule_id,
        ]);
        
        $actor = $this->createSystemUser();
        
        $this->service->rescheduleTicket($ticket, $newVoyage->id, $scenario['class']->id, $actor);

        // 10% of 50,000 = 5,000 penalty
        $agentAccount->refresh();
        $this->assertEquals(95000.0, $agentAccount->balance);
        
        $this->assertDatabaseHas('transactions', [
            'amount' => 5000,
            'payment_reference' => $ticket->ticket_number,
        ]);
    }

    /** @test */
    public function it_splits_booking_when_rescheduling_to_different_vessel_or_date(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        
        // Add a second ticket to the booking
        $ticket2 = Ticket::create([
            'booking_id' => $booking->id,
            'voyage_id' => $scenario['voyage']->id,
            'ferry_class_id' => $scenario['class']->id,
            'passenger_id' => $this->createPassenger()->id,
            'passenger_type_id' => 1,
            'ticket_number' => $booking->booking_reference . '-2',
            'base_fare' => 35000,
            'final_fare' => 35000,
            'status' => 'confirmed',
            'qr_code_token' => \Illuminate\Support\Str::random(32),
        ]);
        
        $ticket1 = $booking->items->first();
        
        $newVoyage = $this->createVoyage($scenario['route'], $scenario['voyage']->ferry, [
            'departure_date' => now()->addDays(2)->format('Y-m-d'),
            'departure_time' => '10:00:00',
            'status' => 'scheduled',
            'schedule_id' => $scenario['voyage']->schedule_id,
        ]);
        
        $actor = $this->createSystemUser();
        
        // Reschedule only ticket 1
        $updatedTicket1 = $this->service->rescheduleTicket($ticket1, $newVoyage->id, $scenario['class']->id, $actor);

        // Verify ticket 1 is on a new booking
        $this->assertNotEquals($booking->id, $updatedTicket1->booking_id);
        $this->assertEquals('confirmed', $updatedTicket1->status);
        
        // Verify old ticket 1 is cancelled
        $ticket1->refresh();
        $this->assertEquals('cancelled', $ticket1->status);
        
        // Verify ticket 2 remains on original booking
        $ticket2->refresh();
        $this->assertEquals($booking->id, $ticket2->booking_id);
        $this->assertEquals('confirmed', $ticket2->status);
    }

    /** @test */
    public function it_prevents_rescheduling_cancelled_or_boarded_tickets(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $ticket = $booking->items->first();
        $ticket->update(['status' => 'cancelled']);
        
        $actor = $this->createSystemUser();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot reschedule cancelled or boarded ticket');

        $this->service->rescheduleTicket($ticket, $scenario['voyage']->id, $scenario['class']->id, $actor);
    }
}
