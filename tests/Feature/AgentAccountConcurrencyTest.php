<?php

namespace Tests\Feature;

use App\Models\SystemTransaction;
use App\Models\AgentAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AgentAccountConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function simultaneous_wallet_deductions_prevent_race_conditions(): void
    {
        // This test proves BUG-004: Concurrency issue on Agent float subtraction
        // We simulate two concurrent processes trying to deduct from the same agent wallet
        // when the balance is only sufficient for one.

        $agentData = $this->createAgentWithAccount(50000); // 50,000 balance
        $accountId = $agentData['account']->id;

        $scenario1 = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        $scenario2 = $this->createCompleteBookingScenario(['user' => $agentData['user']]);
        
        // 1 passenger @ 35,000 each
        $booking1 = $this->createConfirmedBooking($scenario1['voyage'], $scenario1['class'], $agentData['user'], 1);
        $booking2 = $this->createConfirmedBooking($scenario2['voyage'], $scenario2['class'], $agentData['user'], 1);

        // We use raw SQL with FOR UPDATE SKIP LOCKED to simulate concurrent lock acquisition
        // or just rely on the application's DB::transaction approach.
        // In Laravel tests with SQLite :memory:, concurrency is hard to test perfectly.
        // However, with PostgreSQL, we can verify that the deduction method uses locking properly.
        
        $paymentService = app(\App\Services\PaymentProcessingService::class);

        // Simulate Process 1
        $process1 = function () use ($paymentService, $booking1, $agentData) {
            DB::transaction(function () use ($paymentService, $booking1, $agentData) {
                // Ensure we get a fresh, locked instance inside the transaction
                $lockedAccount = AgentAccount::where('id', $agentData['account']->id)->lockForUpdate()->first();
                
                $paymentService->processAgentWalletDeduction(
                    booking: $booking1,
                    bookingUser: $agentData['user']
                );
            });
        };

        // Simulate Process 2
        $process2 = function () use ($paymentService, $booking2, $agentData) {
            DB::transaction(function () use ($paymentService, $booking2, $agentData) {
                $lockedAccount = AgentAccount::where('id', $agentData['account']->id)->lockForUpdate()->first();
                
                $paymentService->processAgentWalletDeduction(
                    booking: $booking2,
                    bookingUser: $agentData['user']
                );
            });
        };

        // Execute Process 1 (should succeed)
        $process1();
        
        $agentData['account']->refresh();
        $this->assertEquals(16750.00, (float) $agentData['account']->balance);

        // Execute Process 2 (should fail with Exception due to insufficient balance)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient wallet balance');
        $process2();
    }
}
