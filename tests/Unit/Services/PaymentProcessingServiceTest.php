<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\SystemTransaction;
use App\Models\Ticket;
use App\Models\TransactionCategory;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected PaymentProcessingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PaymentProcessingService::class);
    }

    /** @test */
    public function it_records_cash_payment_with_transaction_ledger(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 2);

        // Simulate cash payment
        $this->service->processStandardPayment(
            booking: $booking,
            paymentData: [
                'amount' => (float) $booking->net_amount,
                'currency' => 'TZS',
                'payment_channel' => 'cash',
                'transaction_reference' => 'CASH-' . uniqid()
            ]
        );

        // Verify transaction was created
        $transaction = SystemTransaction::where('booking_id', $booking->id)->first();
        $this->assertNotNull($transaction, 'SystemTransaction should be created for cash payment');
        $this->assertEquals((float) $booking->net_amount, (float) $transaction->amount);
        $this->assertEquals('TZS', $transaction->currency_code);
    }

    /** @test */
    public function it_records_usd_payment_with_exchange_rate(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        $booking = Booking::create([
            'booking_reference' => 'VFF-' . mt_rand(100000000000, 999999999999),
            'user_id' => $user->id,
            'booked_by_user_id' => $user->id,
            'booking_source' => 'WEB',
            'status' => 'held',
            'trip_type' => 'one_way',
            'total_amount' => 30,
            'total_amount_tzs' => 78000,
            'total_amount_usd' => 30,
            'net_amount' => 30,
            'currency' => 'USD',
            'exchange_rate' => 2600.000000,
        ]);

        $passenger = $this->createPassenger();
        Ticket::create([
            'booking_id' => $booking->id,
            'ticket_number' => $booking->booking_reference . '-1',
            'voyage_id' => $scenario['voyage']->id,
            'ferry_class_id' => $scenario['class']->id,
            'passenger_id' => $passenger->id,
            'seat_number' => 'OPEN',
            'base_fare' => 15,
            'discount_amount' => 0,
            'final_fare' => 15,
            'status' => 'confirmed',
            'currency' => 'USD',
            'qr_code_token' => bin2hex(random_bytes(16)),
        ]);

        $this->service->processStandardPayment(
            booking: $booking,
            paymentData: [
                'amount' => 30,
                'currency' => 'USD',
                'payment_channel' => 'card',
                'transaction_reference' => 'CARD-' . uniqid()
            ]
        );

        $transaction = SystemTransaction::where('booking_id', $booking->id)->where('currency_code', 'USD')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals('USD', $transaction->currency_code);
        $this->assertEquals(30.00, (float) $transaction->amount);
    }

    /** @test */
    public function it_deducts_agent_wallet_with_commission(): void
    {
        $agentData = $this->createAgentWithAccount(500000, [
            'commission_rate' => 5.00,
            'commission_method' => 'post_sale',
        ]);

        $scenario = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user'], 1);

        $amount = (float) $booking->net_amount;

        $this->service->processAgentWalletDeduction(
            booking: $booking,
            bookingUser: $agentData['user']
        );

        // Verify wallet was deducted
        $agentData['account']->refresh();
        $expectedBalance = 500000 - $amount;
        $this->assertEquals($expectedBalance, (float) $agentData['account']->balance);

        // Verify at least one transaction exists for the deduction
        $deductionTx = SystemTransaction::where('booking_id', $booking->id)
            ->whereHas('transactionCategory', fn($q) => $q->where('slug', 'ticket-sale'))
            ->first();
        $this->assertNotNull($deductionTx, 'Deduction transaction should exist for agent wallet payment');
    }

    /** @test */
    public function it_prevents_payment_with_insufficient_balance(): void
    {
        $agentData = $this->createAgentWithAccount(10000); // Only 10k balance

        $scenario = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        // 2 passengers @ 35000 each = 70000 > 10000
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user'], 2);

        $this->expectException(\Exception::class);

        $this->service->processAgentWalletDeduction(
            booking: $booking,
            bookingUser: $agentData['user']
        );
    }

    /** @test */
    public function commission_is_credited_for_pre_deposit_method(): void
    {
        $agentData = $this->createAgentWithAccount(500000, [
            'commission_rate' => 10.00,
            'commission_method' => 'pre_deposit',
        ]);

        $scenario = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user'], 1);
        $amount = (float) $booking->net_amount; // 35000

        $this->service->processAgentWalletDeduction(
            booking: $booking,
            bookingUser: $agentData['user']
        );

        // Check commission transaction
        $commTx = SystemTransaction::where('booking_id', $booking->id)
            ->whereHas('transactionCategory', fn($q) => $q->where('slug', 'commission'))
            ->first();

        $this->assertNotNull($commTx, 'Commission transaction should be created for post_sale agent');
        $expectedComm = round($amount * 10 / 100, 4);
        $this->assertEquals($expectedComm, (float) $commTx->amount);
    }

    /** @test */
    public function no_commission_for_post_sale_method(): void
    {
        $agentData = $this->createAgentWithAccount(500000, [
            'commission_rate' => 5.00,
            'commission_method' => 'post_sale',
        ]);

        $scenario = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agentData['user'], 1);

        $this->service->processAgentWalletDeduction(
            booking: $booking,
            bookingUser: $agentData['user']
        );

        // No commission transaction should exist for pre_deposit
        $commTx = SystemTransaction::where('booking_id', $booking->id)
            ->whereHas('transactionCategory', fn($q) => $q->where('slug', 'commission'))
            ->first();

        $this->assertNull($commTx, 'No commission should be created for pre_deposit agents');
    }
}
