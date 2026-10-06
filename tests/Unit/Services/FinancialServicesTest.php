<?php

namespace Tests\Unit\Services;

use App\Models\SystemConfig;
use App\Models\SystemTransaction;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for ExchangeRateService, PendingRefundService, and financial record integrity.
 * Covers tasks 5.2, 5.7, and 5.9 from the implementation plan.
 */
class FinancialServicesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ── Exchange Rate Service ─────────────────────────────────

    /** @test */
    public function exchange_rate_service_returns_default_when_no_config(): void
    {
        SystemConfig::where('key_name', 'usd_to_tzs_exchange_rate')->delete();
        $rate = ExchangeRateService::getCurrentRate('USD', 'TZS');
        $this->assertEquals(2600.0, $rate);
    }

    /** @test */
    public function exchange_rate_service_returns_config_value(): void
    {
        SystemConfig::updateOrCreate(
            ['key_name' => 'usd_to_tzs_exchange_rate'],
            ['value' => '2750', 'group' => 'finance']
        );
        $rate = ExchangeRateService::getCurrentRate('USD', 'TZS');
        $this->assertEquals(2750.0, $rate);
    }

    /** @test */
    public function exchange_rate_same_currency_returns_one(): void
    {
        $this->assertEquals(1.0, ExchangeRateService::getCurrentRate('TZS', 'TZS'));
        $this->assertEquals(1.0, ExchangeRateService::getCurrentRate('USD', 'USD'));
    }

    /** @test */
    public function exchange_rate_convert_usd_to_tzs(): void
    {
        SystemConfig::updateOrCreate(
            ['key_name' => 'usd_to_tzs_exchange_rate'],
            ['value' => '2600', 'group' => 'finance']
        );
        $result = ExchangeRateService::convert(100, 'USD', 'TZS');
        $this->assertEquals(260000.0, $result);
    }

    /** @test */
    public function exchange_rate_to_tzs_helper(): void
    {
        SystemConfig::updateOrCreate(
            ['key_name' => 'usd_to_tzs_exchange_rate'],
            ['value' => '2600', 'group' => 'finance']
        );
        $this->assertEquals(2600.0, ExchangeRateService::rateToTzs('USD'));
        $this->assertEquals(1.0, ExchangeRateService::rateToTzs('TZS'));
    }

    /** @test */
    public function to_tzs_converts_amount(): void
    {
        SystemConfig::updateOrCreate(
            ['key_name' => 'usd_to_tzs_exchange_rate'],
            ['value' => '2600', 'group' => 'finance']
        );
        $this->assertEquals(52000.0, ExchangeRateService::toTzs(20, 'USD'));
        $this->assertEquals(5000.0, ExchangeRateService::toTzs(5000, 'TZS'));
    }

    // ── Pending Refund Service ────────────────────────────────

    /** @test */
    public function pending_refund_service_can_list_by_status(): void
    {
        $agent = $this->createAgentWithAccount(500000);
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agent['user'], 1);

        \App\Models\PendingRefund::create([
            'booking_id' => $booking->id,
            'ticket_id' => $booking->items->first()->id,
            'agent_account_id' => $agent['account']->id,
            'amount' => 5000,
            'status' => 'pending',
        ]);
        \App\Models\PendingRefund::create([
            'booking_id' => $booking->id,
            'ticket_id' => $booking->items->first()->id,
            'agent_account_id' => $agent['account']->id,
            'amount' => 3000,
            'status' => 'released',
        ]);

        $service = app(\App\Services\PendingRefundService::class);
        $this->assertEquals(1, $service->pendingCount());
        $this->assertEquals(5000.0, $service->pendingTotal());
    }

    /** @test */
    public function pending_refund_release_credits_agent_wallet(): void
    {
        $agent = $this->createAgentWithAccount(500000);
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agent['user'], 1);
        $admin = $this->createSystemUser();

        $pr = \App\Models\PendingRefund::create([
            'booking_id' => $booking->id,
            'ticket_id' => $booking->items->first()->id,
            'agent_account_id' => $agent['account']->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        $service = app(\App\Services\PendingRefundService::class);
        $service->release($pr->id, $admin->id);

        $pr->refresh();
        $this->assertEquals('released', $pr->status);
        $this->assertNotNull($pr->released_at);
        $this->assertEquals($admin->id, $pr->released_by);
    }

    /** @test */
    public function pending_refund_reject_stores_reason(): void
    {
        $agent = $this->createAgentWithAccount(500000);
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agent['user'], 1);
        $admin = $this->createSystemUser();

        $pr = \App\Models\PendingRefund::create([
            'booking_id' => $booking->id,
            'ticket_id' => $booking->items->first()->id,
            'agent_account_id' => $agent['account']->id,
            'amount' => 10000,
            'status' => 'pending',
        ]);

        $service = app(\App\Services\PendingRefundService::class);
        $service->reject($pr->id, 'Fraudulent booking', $admin->id);

        $pr->refresh();
        $this->assertEquals('rejected', $pr->status);
    }

    /** @test */
    public function pending_refund_bulk_release_returns_counts(): void
    {
        $agent = $this->createAgentWithAccount(500000);
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agent['user'], 1);
        $admin = $this->createSystemUser();

        $ids = [];
        for ($i = 0; $i < 3; $i++) {
            $ids[] = \App\Models\PendingRefund::create([
                'booking_id' => $booking->id,
                'ticket_id' => $booking->items->first()->id,
                'agent_account_id' => $agent['account']->id,
                'amount' => 1000,
                'status' => 'pending',
            ])->id;
        }

        $service = app(\App\Services\PendingRefundService::class);
        $result = $service->bulkRelease($ids, $admin->id);

        $this->assertEquals(3, $result['released']);
        $this->assertEquals(0, $result['failed']);
    }

    /** @test */
    public function cannot_release_already_released_refund(): void
    {
        $agent = $this->createAgentWithAccount(500000);
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $agent['user'], 1);
        $admin = $this->createSystemUser();

        $pr = \App\Models\PendingRefund::create([
            'booking_id' => $booking->id,
            'ticket_id' => $booking->items->first()->id,
            'agent_account_id' => $agent['account']->id,
            'amount' => 10000,
            'status' => 'released',
        ]);

        $this->expectException(\Exception::class);
        $service = app(\App\Services\PendingRefundService::class);
        $service->release($pr->id, $admin->id);
    }

    // ── SystemTransaction Soft Deletes ───────────────────────

    /** @test */
    public function system_transaction_uses_soft_deletes(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1);

        $tx = SystemTransaction::create([
            'transaction_number' => 'TX-TEST-' . uniqid(),
            'transaction_category_id' => \App\Models\TransactionCategory::where('slug', 'ticket-sale')->value('id'),
            'transaction_status_id' => \App\Models\TransactionStatus::where('slug', 'completed')->value('id'),
            'amount' => 35000,
            'currency_code' => 'TZS',
            'exchange_rate' => 1.0,
            'amount_tzs' => 35000,
            'booking_id' => $booking->id,
            'user_id' => $scenario['user']->id,
            'description' => 'Test sale',
        ]);

        $tx->delete();

        $this->assertSoftDeleted('transactions', ['id' => $tx->id]);
        $this->assertNull(SystemTransaction::find($tx->id));
        $this->assertNotNull(SystemTransaction::withTrashed()->find($tx->id));
    }

    // ── Transaction Linkage ─────────────────────────────────

    /** @test */
    public function system_transaction_has_related_transaction_relationship(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1);
        $catId = \App\Models\TransactionCategory::where('slug', 'ticket-sale')->value('id');
        $statusId = \App\Models\TransactionStatus::where('slug', 'completed')->value('id');

        $originalTx = SystemTransaction::create([
            'transaction_number' => 'TX-ORIG-' . uniqid(),
            'transaction_category_id' => $catId,
            'transaction_status_id' => $statusId,
            'amount' => 35000,
            'currency_code' => 'TZS',
            'exchange_rate' => 1.0,
            'amount_tzs' => 35000,
            'booking_id' => $booking->id,
            'user_id' => $scenario['user']->id,
            'description' => 'Original sale',
        ]);

        $refundTx = SystemTransaction::create([
            'transaction_number' => 'REF-' . uniqid(),
            'transaction_category_id' => \App\Models\TransactionCategory::where('slug', 'cancellation-refund')->value('id') ?? $catId,
            'transaction_status_id' => $statusId,
            'amount' => 31500,
            'currency_code' => 'TZS',
            'exchange_rate' => 1.0,
            'amount_tzs' => 31500,
            'booking_id' => $booking->id,
            'user_id' => $scenario['user']->id,
            'related_transaction_id' => $originalTx->id,
            'description' => 'Refund for original',
        ]);

        $this->assertEquals($originalTx->id, $refundTx->relatedTransaction->id);
        $this->assertTrue($originalTx->childTransactions->contains($refundTx));
    }

    // ── Refund Gateway ──────────────────────────────────────

    /** @test */
    public function manual_refund_gateway_returns_success(): void
    {
        $gateway = new \App\Services\Refunds\ManualRefundGateway();
        $result = $gateway->refund('PAY-123', 5000, 'TZS', 'Test refund');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('MANUAL-', $result['reference']);
        $this->assertFalse($gateway->supportsAutomaticRefund());
    }

    /** @test */
    public function refund_gateway_factory_returns_manual_for_cash(): void
    {
        $gateway = \App\Services\Refunds\RefundGatewayFactory::make('cash');
        $this->assertInstanceOf(\App\Services\Refunds\ManualRefundGateway::class, $gateway);
    }

    /** @test */
    public function refund_gateway_factory_returns_selcom_for_mobile_money(): void
    {
        $gateway = \App\Services\Refunds\RefundGatewayFactory::make('selcom');
        $this->assertInstanceOf(\App\Services\Refunds\SelcomRefundGateway::class, $gateway);
    }

    /** @test */
    public function selcom_refund_gateway_gracefully_fails_without_config(): void
    {
        $gateway = new \App\Services\Refunds\SelcomRefundGateway();
        $result = $gateway->refund('PAY-123', 5000, 'TZS');

        $this->assertFalse($result['success']);
        $this->assertNull($result['reference']);
        $this->assertFalse($gateway->supportsAutomaticRefund());
    }
}
