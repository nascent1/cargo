<?php

namespace Tests\Unit\Services;

use App\Models\Agent;
use App\Models\AgentAccount;
use App\Models\Booking;
use App\Models\SystemTransaction;
use App\Models\Ticket;
use App\Services\BookingCancellationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingCancellationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingCancellationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingCancellationService::class);
    }

    #[Test]
    public function it_cancels_a_full_booking_and_records_penalty(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);

        $result = $this->service->cancelBooking($booking, 'Customer request', $user);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
        $this->assertEquals('cancelled', $booking->items->first()->status);

        // Verify penalty transaction (10% of 35000 = 3500)
        $penaltyTx = SystemTransaction::where('booking_id', $booking->id)
            ->whereHas('transactionCategory', fn($q) => $q->where('slug', 'cancellation_penalty'))
            ->first();
        
        $this->assertNotNull($penaltyTx);
        $this->assertEquals(3500.00, (float) $penaltyTx->amount);
    }

    #[Test]
    public function it_calculates_penalty_based_on_config(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);

        // Change config to 20%
        \App\Models\SystemConfig::setValue('cancellation_penalty_percentage', '20');

        $this->service->cancelBooking($booking, 'Customer request', $user);

        $penaltyTx = SystemTransaction::where('booking_id', $booking->id)
            ->whereHas('transactionCategory', fn($q) => $q->where('slug', 'cancellation_penalty'))
            ->first();
        
        $this->assertNotNull($penaltyTx);
        $this->assertEquals(7000.00, (float) $penaltyTx->amount); // 20% of 35000
    }

    #[Test]
    public function it_cancels_individual_ticket_without_cancelling_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 2);

        $ticketToCancel = $booking->items->first();
        $ticketToKeep = $booking->items->last();

        $this->service->cancelTicket($ticketToCancel, 'Individual request', $user);

        $booking->refresh();
        $ticketToCancel->refresh();
        $ticketToKeep->refresh();

        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('cancelled', $ticketToCancel->status);
        $this->assertEquals('confirmed', $ticketToKeep->status);
    }

    #[Test]
    public function it_cancels_entire_booking_if_all_tickets_are_cancelled(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 2);

        $this->service->cancelTicket($booking->items[0], 'Request 1', $user);
        
        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);

        $this->service->cancelTicket($booking->items[1], 'Request 2', $user);

        $booking->refresh();
        $this->assertEquals('cancelled', $booking->status);
    }

    #[Test]
    public function it_prevents_cancellation_of_boarded_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);
        
        $ticket = $booking->items->first();
        $ticket->update(['status' => 'boarded']);

        $this->expectException(\Exception::class);
        $this->service->cancelTicket($ticket, 'Too late', clone $user);
    }

    #[Test]
    public function it_prevents_cancellation_past_allowed_window(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        // Set departure to 1 hour from now
        $scenario['voyage']->update([
            'departure_date' => now()->toDateString(),
            'departure_time' => now()->addHour()->toTimeString(),
        ]);

        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);

        \App\Models\SystemConfig::setValue('cancellation_allowed_hours_before', '2');

        $this->expectException(\Exception::class);
        $this->service->cancelBooking($booking, 'Too late', clone $user);
    }

    #[Test]
    public function agent_immediate_refund_credits_wallet(): void
    {
        $agentData = $this->createAgentWithAccount(100000, [
            'cancellation_refund_mode' => 'immediate',
        ]);
        
        $scenario = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user'], 1);
        $booking->update(['payment_channel' => 'agent_float']);

        // Record initial sale transaction for context
        $this->seedTransactionCategories();
        $catId = \App\Models\TransactionCategory::getIdBySlug('ticket-sale');
        SystemTransaction::create([
            'transaction_number' => 'TX-' . uniqid(),
            'transaction_category_id' => $catId,
            'payment_type_id' => 1,
            'amount' => 35000,
            'currency_code' => 'TZS',
            'amount_tzs' => 35000,
            'booking_id' => $booking->id,
            'user_id' => $agentData['user']->id,
            'description' => 'Initial sale'
        ]);

        $this->service->cancelBooking($booking, 'Refund test', clone $agentData['user']);

        $agentData['account']->refresh();
        
        // 100000 (initial) + 35000 (refund) - 3500 (penalty) - 1750 (commission reversal) = 129750
        $this->assertEquals(129750.00, (float) $agentData['account']->balance);
    }
}
