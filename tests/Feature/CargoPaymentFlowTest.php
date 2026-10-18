<?php

namespace Tests\Feature;

use App\Models\CargoInventory;
use App\Models\CargoPayment;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\IntegrationConfig;
use App\Models\PaymentMethod;
use App\Models\SystemConfig;
use App\Models\SystemTransaction;
use App\Models\TransportService;
use App\Models\User;
use App\Services\Cargo\CargoPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Cargo payments: cash, USSD push (simulator + Airtel driver over faked HTTP), Lipa Namba,
 * callbacks, sweep / auto-cancel and guards.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoPaymentFlowTest extends TestCase
{
    use DatabaseTransactions;

    private User $officer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officer = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))->first()
            ?? $this->markTestSkipped('Needs an admin user in the database.');

        SystemConfig::setValue('cargo_payment_simulator_enabled', '1');
        SystemConfig::setValue('cargo_ussd_timeout_minutes', '15');
        SystemConfig::setValue('cargo_ussd_max_pushes', '3');
        SystemConfig::setValue('sms_enabled', '0'); // no customer SMS from tests
        PaymentMethod::where('slug', 'lipa_namba')->update(['account_number' => '123456', 'account_name' => 'UCLP CARGO', 'is_active' => true]);
    }

    private function payments(): CargoPaymentService
    {
        return app(CargoPaymentService::class);
    }

    private function method(string $slug): PaymentMethod
    {
        return PaymentMethod::where('slug', $slug)->firstOrFail();
    }

    /**
     * A booking holding 100 kg of inventory, awaiting payment.
     */
    private function pendingConsignment(): Consignment
    {
        $service = TransportService::whereHas('cargoInventory')->first() ?? $this->markTestSkipped('Needs a transport service with cargo inventory.');
        $type = CargoType::first() ?? $this->markTestSkipped('Needs a cargo type.');

        CargoInventory::where('transport_service_id', $service->id)->increment('used_weight_kg', 100);

        $consignment = Consignment::create([
            'uuid' => (string) Str::uuid(),
            'consignment_number' => 'CGTEST' . strtoupper(Str::random(6)),
            'tracking_number' => strtoupper(Str::random(10)),
            'qr_code_token' => 'CQ-' . strtoupper(Str::random(12)),
            'transport_service_id' => $service->id,
            'route_id' => $service->route_id,
            'sender_name' => 'Test Sender', 'sender_phone' => '0754000001',
            'receiver_name' => 'Test Receiver', 'receiver_phone' => '0754000002',
            'total_amount_tzs' => 50000, 'net_amount' => 50000,
            'total_weight_kg' => 100,
            'status' => 'pending_payment', 'payment_status' => 'unpaid',
            'booked_by_user_id' => $this->officer->id,
        ]);
        $consignment->items()->create([
            'cargo_type_id' => $type->id, 'item_number' => $consignment->consignment_number . '-01',
            'weight_kg' => 100, 'final_fare' => 50000, 'status' => 'pending_payment',
            'qr_code_token' => 'CI' . strtoupper(Str::random(12)),
        ]);

        return $consignment;
    }

    private function usedWeight(Consignment $c): float
    {
        return (float) CargoInventory::where('transport_service_id', $c->transport_service_id)->value('used_weight_kg');
    }

    public function test_cash_payment_completes_the_booking_with_ledger_and_receipt(): void
    {
        $c = $this->pendingConsignment();

        $payment = $this->payments()->start($c, $this->method('cash'), null, $this->officer);

        $c->refresh();
        $this->assertSame('paid', $payment->status);
        $this->assertSame('booked', $c->status);
        $this->assertSame('paid', $c->payment_status);
        $this->assertNotNull($c->paid_at);
        $this->assertSame(1, SystemTransaction::where('consignment_id', $c->id)->count());
        $this->assertSame(1, $c->receipts()->count());
    }

    public function test_simulated_push_is_sent_then_paid_and_replays_are_noops(): void
    {
        $c = $this->pendingConsignment();
        $payment = $this->payments()->start($c, $this->method('mpesa'), '0754 123 456', $this->officer);

        // QUEUE_CONNECTION=sync in tests: the push job already ran after commit
        $payment->refresh();
        $this->assertTrue($payment->isSimulated());
        $this->assertSame('awaiting_customer', $payment->status);
        $this->assertSame('255754123456', $payment->payer_msisdn);

        Carbon::setTestNow(now()->addSeconds(10));
        $this->payments()->refreshStatus($payment);
        Carbon::setTestNow();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('booked', $c->fresh()->status);

        // A late duplicate outcome must not double-record anything
        $this->payments()->applyResult($payment->fresh(), \App\Gateways\Payment\Push\PushResult::paid('DUP'), 'callback');
        $this->assertSame(1, SystemTransaction::where('consignment_id', $c->id)->count());
        $this->assertSame(1, $c->receipts()->count());
    }

    public function test_push_channel_without_credentials_leaves_the_booking_awaiting_payment(): void
    {
        SystemConfig::setValue('cargo_payment_simulator_enabled', '0');
        \App\Models\IntegrationConfig::where('channel', 'payment')->where('provider', 'vodacom_mpesa')->update(['is_active' => false]);

        // Still offered at checkout, flagged as not live
        $option = collect(PaymentMethod::activeOptions())->firstWhere('slug', 'mpesa');
        $this->assertNotNull($option);
        $this->assertFalse($option['live']);
        $this->assertFalse($option['simulated']);

        $c = $this->pendingConsignment();
        $before = $this->usedWeight($c);
        $payment = $this->payments()->start($c, $this->method('mpesa'), '0754 123 456', $this->officer);

        // Nothing pushed, nothing paid: the space stays reserved until paid another way or timed out
        $payment->refresh();
        $this->assertSame('failed', $payment->status);
        $this->assertStringContainsString("isn't live yet", $payment->failure_reason);
        $this->assertFalse($payment->isSimulated());
        $c->refresh();
        $this->assertSame('pending_payment', $c->status);
        $this->assertSame('unpaid', $c->payment_status);
        $this->assertNull($c->paid_at);
        $this->assertSame(0, SystemTransaction::where('consignment_id', $c->id)->count());
        $this->assertSame(0, $c->receipts()->count());

        // Not-live attempts don't use up the push allowance; Lipa Namba still completes it
        $lipa = $this->payments()->start($c, $this->method('lipa_namba'), null, $this->officer);
        $this->payments()->confirmManual($lipa, 'NL' . strtoupper(Str::random(8)), null, $this->officer);
        $this->assertSame('booked', $c->fresh()->status);
        $this->assertSame('paid', $c->fresh()->payment_status);
        $this->assertSame($before, $this->usedWeight($c));
    }

    public function test_unpaid_not_live_booking_is_auto_cancelled_after_the_timeout(): void
    {
        SystemConfig::setValue('cargo_payment_simulator_enabled', '0');
        \App\Models\IntegrationConfig::where('channel', 'payment')->where('provider', 'vodacom_mpesa')->update(['is_active' => false]);

        $c = $this->pendingConsignment();
        $before = $this->usedWeight($c);
        $this->payments()->start($c, $this->method('mpesa'), '0754 123 456', $this->officer);

        $this->payments()->sweep();
        $this->assertSame('pending_payment', $c->fresh()->status);

        Carbon::setTestNow(now()->addMinutes(16));
        $this->payments()->sweep();
        Carbon::setTestNow();

        $this->assertSame('cancelled', $c->fresh()->status);
        $this->assertSame($before - 100, $this->usedWeight($c));
    }

    public function test_push_rejects_a_number_from_another_network(): void
    {
        $c = $this->pendingConsignment();

        $this->expectException(\App\Exceptions\CargoPaymentException::class);
        $this->payments()->start($c, $this->method('mpesa'), '0688123456', $this->officer); // Airtel number
    }

    public function test_failed_push_can_be_resent_to_another_number_and_paid(): void
    {
        $c = $this->pendingConsignment();
        $first = $this->payments()->start($c, $this->method('mpesa'), '0754123450', $this->officer); // ends in 0 → fails

        Carbon::setTestNow(now()->addSeconds(10));
        $this->payments()->refreshStatus($first->fresh());
        Carbon::setTestNow();
        $this->assertSame('failed', $first->fresh()->status);
        $this->assertSame('pending_payment', $c->fresh()->status);

        $response = $this->actingAs($this->officer)
            ->postJson("/admin/cargo/api/payments/{$first->uuid}/resend", ['payer_msisdn' => '0755111222'])
            ->assertOk();
        $second = CargoPayment::where('uuid', $response->json('uuid'))->firstOrFail();

        Carbon::setTestNow(now()->addSeconds(10));
        $this->payments()->refreshStatus($second);
        Carbon::setTestNow();

        $this->assertSame('paid', $second->fresh()->status);
        $this->assertSame('booked', $c->fresh()->status);
    }

    public function test_airtel_callback_is_confirmed_with_the_provider_and_forged_ones_are_ignored(): void
    {
        IntegrationConfig::where('provider', 'airtel_money')->first()->forceFill([
            'is_active' => true, 'environment' => 'sandbox', 'sandbox_url' => 'https://openapiuat.airtel.africa',
            'api_key' => 'client', 'api_secret' => 'secret', 'extra_credentials' => ['callback_token' => 'tok123'],
        ])->save();

        $status = 'TIP';
        Http::fake([
            '*/auth/oauth2/token' => Http::response(['access_token' => 'AT', 'expires_in' => 3600]),
            '*/merchant/v1/payments/' => Http::response(['data' => ['transaction' => ['id' => 'x', 'status' => 'Success.']], 'status' => ['success' => true, 'message' => 'Success.']]),
            '*/standard/v1/payments/*' => function () use (&$status) {
                return Http::response(['data' => ['transaction' => ['status' => $status, 'airtel_money_id' => 'AM777', 'message' => 'x']]]);
            },
        ]);

        $c = $this->pendingConsignment();
        $payment = $this->payments()->start($c, $this->method('airtel_money'), '0688123456', $this->officer)->fresh();
        $this->assertFalse($payment->isSimulated());
        $this->assertSame('awaiting_customer', $payment->status);

        // Forged "success" without the token while Airtel still says in-progress → ignored
        $this->postJson('/api/v1/payments/callback/airtel_money', ['transaction' => ['id' => $payment->reference, 'status_code' => 'TS', 'airtel_money_id' => 'FAKE']])
            ->assertOk();
        $this->assertSame('awaiting_customer', $payment->fresh()->status);

        // Genuine callback; Airtel's status enquiry confirms success
        $status = 'TS';
        $this->postJson("/api/v1/payments/callback/airtel_money?token=tok123", ['transaction' => ['id' => $payment->reference, 'status_code' => 'TS', 'airtel_money_id' => 'AM777']])
            ->assertOk();
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('AM777', $payment->fresh()->provider_reference);
        $this->assertSame('booked', $c->fresh()->status);

        // Replay
        $this->postJson("/api/v1/payments/callback/airtel_money?token=tok123", ['transaction' => ['id' => $payment->reference, 'status_code' => 'TS', 'airtel_money_id' => 'AM777']])
            ->assertOk();
        $this->assertSame(1, SystemTransaction::where('consignment_id', $c->id)->count());
    }

    public function test_lipa_namba_confirmation_and_duplicate_reference_is_rejected(): void
    {
        $first = $this->pendingConsignment();
        $p1 = $this->payments()->start($first, $this->method('lipa_namba'), null, $this->officer);
        $this->assertSame('awaiting_customer', $p1->status);

        $this->actingAs($this->officer)
            ->postJson("/admin/cargo/api/payments/{$p1->uuid}/confirm", ['reference' => 'pp12 abc 789'])
            ->assertOk()->assertJsonPath('status', 'paid')->assertJsonPath('manual_reference', 'PP12ABC789');
        $this->assertSame('booked', $first->fresh()->status);

        $second = $this->pendingConsignment();
        $p2 = $this->payments()->start($second, $this->method('lipa_namba'), null, $this->officer);
        $this->actingAs($this->officer)
            ->postJson("/admin/cargo/api/payments/{$p2->uuid}/confirm", ['reference' => 'PP12ABC789'])
            ->assertStatus(422)
            ->assertJsonFragment(['error' => "Reference PP12ABC789 was already used for booking {$first->consignment_number}. Check the SMS again."]);
        $this->assertSame('pending_payment', $second->fresh()->status);
    }

    public function test_unpaid_booking_is_auto_cancelled_and_space_released(): void
    {
        $c = $this->pendingConsignment();
        $this->payments()->start($c, $this->method('lipa_namba'), null, $this->officer);
        $before = $this->usedWeight($c);

        Carbon::setTestNow(now()->addMinutes(31));
        $this->payments()->sweep();
        Carbon::setTestNow();

        $c->refresh();
        $this->assertSame('cancelled', $c->status);
        $this->assertSame($before - 100, $this->usedWeight($c));
        $this->assertSame('expired', $c->payments()->first()->status);
    }

    public function test_sweep_does_not_cancel_a_booking_paid_at_the_last_second(): void
    {
        $c = $this->pendingConsignment();
        $payment = $this->payments()->start($c, $this->method('mpesa'), '0754123456', $this->officer);

        // Past the deadline, but the provider reports the customer paid → completed, not cancelled
        Carbon::setTestNow(now()->addMinutes(16));
        $this->payments()->sweep();
        Carbon::setTestNow();

        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('booked', $c->fresh()->status);
    }

    public function test_guards_block_printing_status_changes_and_scanning_until_paid(): void
    {
        $c = $this->pendingConsignment();

        $this->actingAs($this->officer)->get("/admin/cargo/bookings/{$c->id}/print")->assertStatus(409);

        // Loading and supervisor corrections both refuse an unpaid booking
        $this->actingAs($this->officer)->withSession(['2fa_verified' => true])
            ->post("/admin/cargo/bookings/{$c->id}/load")
            ->assertSessionHas('error');
        $this->actingAs($this->officer)->withSession(['2fa_verified' => true])
            ->patch("/admin/cargo/bookings/{$c->id}/status", ['status' => 'loaded', 'reason' => 'Trying to load unpaid cargo'])
            ->assertSessionHas('error');
        $this->assertSame('pending_payment', $c->fresh()->status);

        Sanctum::actingAs($this->officer);
        $this->postJson('/api/v1/scanner/cargo/status/update', ['type' => 'consignment', 'id' => $c->id, 'status' => 'loaded'])
            ->assertStatus(422);
        $this->assertSame('pending_payment', $c->fresh()->status);
    }
}
