<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\IntegrationConfig;
use App\Models\NotificationLog;
use App\Models\Role;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use App\Services\DualControlService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Port operations: load (booking page, manifest tick/scan, scanner) → depart (in transit; paid
 * bookings not loaded are left behind) → arrive → hand over with an OTP-confirmed mobile;
 * supervisor corrections; Port officer role. SMS go to a faked test gateway only.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoOperationsTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $officer;
    private TransportService $service;
    private int $phone = 0;
    private int $gatewayStatus = 200;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutDefer();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first() ?? $this->markTestSkipped('Needs an admin user.');
        $role = Role::where('slug', 'port_officer')->first() ?? $this->markTestSkipped('Needs the port_officer role (migration).');
        $this->officer = User::forceCreate([
            'email' => 'port-' . Str::random(8) . '@example.test', 'password' => bcrypt(Str::random(16)),
            'first_name' => 'Port', 'last_name' => 'Officer', 'domain' => 'system_user', 'status' => 'active', 'password_changed_at' => now(),
        ]);
        $this->officer->roles()->sync([$role->id]);

        $this->service = TransportService::availableForBooking()->whereHas('route')->whereDoesntHave('consignments')->first()
            ?? $this->markTestSkipped('Needs an empty bookable departure.');
        $this->service->update(['status' => 'scheduled', 'actual_departure_at' => null, 'actual_arrival_at' => null]);

        IntegrationConfig::where('channel', 'sms')->update(['is_active' => false]);
        IntegrationConfig::create([
            'code' => 'TEST_' . strtoupper(Str::random(8)), 'name' => 'Test gateway', 'provider' => 'nextsms', 'channel' => 'sms',
            'environment' => 'production', 'production_url' => 'https://sms-ops.test', 'access_token' => 'test-token',
            'extra_credentials' => ['sender_id' => 'TEST'], 'is_active' => true, 'sort_order' => 1,
        ]);
        // One fake for the whole test; fakeGateway() switches what it answers
        Http::fake(['sms-ops.test/*' => fn () => $this->gatewayStatus === 200
            ? Http::response(['messages' => [['messageId' => 'M-1', 'status' => ['groupName' => 'PENDING']]]], 200)
            : Http::response(['message' => 'Gateway down'], $this->gatewayStatus)]);

        foreach (['sms_enabled' => '1', 'sms_cargo_in_transit' => '1', 'sms_cargo_arrived' => '1', 'default_locale' => 'en',
            'exchange_rate_last_updated' => today()->toDateString()] as $key => $value) {
            SystemConfig::setValue($key, $value);
        }
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'cargo_journey_status_update')->update(['requires_dual_control' => false]);
    }

    private function fakeGateway(int $status = 200): void
    {
        $this->gatewayStatus = $status;
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function booking(string $status = 'booked', array $attrs = []): Consignment
    {
        $type = CargoType::first() ?? $this->markTestSkipped('Needs a cargo type.');
        $n = ++$this->phone;
        $c = Consignment::create(array_merge([
            'uuid' => (string) Str::uuid(), 'consignment_number' => 'CG-OPS-' . strtoupper(Str::random(5)),
            'tracking_number' => strtoupper(Str::random(10)), 'qr_code_token' => 'CQ-' . strtoupper(Str::random(12)),
            'transport_service_id' => $this->service->id, 'route_id' => $this->service->route_id,
            'sender_name' => 'Asha Sender', 'sender_phone' => '07541' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'receiver_name' => 'Juma Receiver', 'receiver_phone' => '07542' . str_pad((string) $n, 5, '0', STR_PAD_LEFT),
            'receiver_id_number' => 'NIDA-12345',
            'total_amount_tzs' => 50000, 'net_amount' => 50000, 'currency' => 'TZS', 'total_weight_kg' => 10,
            'status' => $status, 'payment_status' => $status === 'pending_payment' ? 'unpaid' : 'paid',
        ], $attrs));
        foreach ([1, 2] as $i) {
            $c->items()->create(['cargo_type_id' => $type->id, 'item_number' => "{$c->consignment_number}-0{$i}", 'quantity' => 1,
                'weight_kg' => 5, 'final_fare' => 25000, 'status' => $status === 'pending_payment' ? 'pending_payment' : $status,
                'qr_code_token' => 'CI' . strtoupper(Str::random(12))]);
        }

        return $c;
    }

    private function smsTo(string $localPhone, string $event): ?NotificationLog
    {
        return NotificationLog::where('event', $event)->where('recipient', '+255' . substr($localPhone, 1))->latest('id')->first();
    }

    private function sentTexts(): \Illuminate\Support\Collection
    {
        return Http::recorded()->map(fn ($pair) => $pair[0]['messages'][0]);
    }

    public function test_load_then_depart_moves_loaded_cargo_and_leaves_the_rest_behind(): void
    {
        $a = $this->booking();
        $b = $this->booking();
        $left = $this->booking();
        $unpaid = $this->booking('pending_payment');

        $this->as($this->officer)->post("/admin/cargo/bookings/{$a->id}/load")->assertSessionHas('success');
        $this->as($this->officer)->postJson("/admin/cargo/api/manifest/{$this->service->id}/load", ['code' => $b->items()->first()->qr_code_token])
            ->assertOk()->assertJsonPath('loaded', [$b->consignment_number]);
        $this->as($this->officer)->postJson("/admin/cargo/api/manifest/{$this->service->id}/load", ['ids' => [$unpaid->id]])
            ->assertStatus(422)->assertJsonPath("skipped.{$unpaid->consignment_number}", "{$unpaid->consignment_number} is awaiting payment and can't be loaded.");
        $this->assertSame('loaded', $a->fresh()->status);
        $this->assertSame(['loaded', 'loaded'], $a->items()->pluck('status')->all());

        $this->as($this->officer)->post("/admin/cargo/journeys/{$this->service->id}/depart")->assertSessionHas('success');

        $this->service->refresh();
        $this->assertSame('departed', $this->service->status);
        $this->assertNotNull($this->service->actual_departure_at);
        $this->assertSame('in_transit', $a->fresh()->status);
        $this->assertSame('in_transit', $b->fresh()->status);
        $this->assertSame('booked', $left->fresh()->status);
        $this->assertTrue($left->fresh()->isLeftBehind());
        $this->assertSame('pending_payment', $unpaid->fresh()->status);

        // SMS: in transit to sender and receiver of the travelling bookings only
        $this->assertSame('sent', $this->smsTo($a->sender_phone, 'cargo_in_transit')?->status);
        $this->assertSame('sent', $this->smsTo($a->receiver_phone, 'cargo_in_transit')?->status);
        $this->assertNull($this->smsTo($left->sender_phone, 'cargo_in_transit'));
        $this->assertTrue($this->sentTexts()->contains(fn ($m) => str_contains($m['text'], "Cargo {$a->consignment_number}") && str_contains($m['text'], 'is on the way to')));

        // Too late to load; public tracking explains it
        $this->as($this->officer)->post("/admin/cargo/bookings/{$left->id}/load")->assertSessionHas('error');
        $this->getJson("/api/v1/cargo/track/{$left->consignment_number}")->assertJsonPath('left_behind', true)->assertJsonPath('status_label', 'Left behind');
    }

    public function test_arrival_readies_cargo_for_collection_and_texts(): void
    {
        $a = $this->booking('loaded');
        $this->as($this->officer)->post("/admin/cargo/journeys/{$this->service->id}/arrive")->assertSessionHas('error'); // not departed yet
        $this->as($this->officer)->post("/admin/cargo/journeys/{$this->service->id}/depart");
        $this->as($this->officer)->post("/admin/cargo/journeys/{$this->service->id}/arrive")->assertSessionHas('success');

        $a->refresh();
        $this->assertSame('delivered', $a->status);
        $this->assertNotNull($a->delivered_at);
        $this->assertNotNull($this->service->fresh()->actual_arrival_at);
        $this->assertSame('sent', $this->smsTo($a->receiver_phone, 'cargo_arrived')?->status);
        $this->assertSame('sent', $this->smsTo($a->sender_phone, 'cargo_arrived')?->status);
    }

    public function test_with_the_journey_rule_on_cargo_moves_only_once_approved(): void
    {
        DualControlConfig::where('operation_type', 'cargo_journey_status_update')->update(['requires_dual_control' => true]);
        $a = $this->booking('loaded');

        $this->as($this->officer)->post("/admin/cargo/journeys/{$this->service->id}/depart")->assertSessionHas('success');
        $this->assertSame('loaded', $a->fresh()->status, 'nothing moves while the departure awaits approval');

        $request = DualControlRequest::where('operation_type', 'cargo_journey_status_update')->where('resource_id', $this->service->id)->latest('id')->firstOrFail();
        $this->actingAs($this->admin);
        app(DualControlService::class)->approve($request);

        $this->assertSame('in_transit', $a->fresh()->status);
    }

    public function test_the_status_dropdown_can_no_longer_depart_or_arrive(): void
    {
        $this->as($this->admin)->patch("/admin/cargo/journeys/{$this->service->id}/status", ['status' => 'departed'])->assertSessionHasErrors('status');
        $this->as($this->admin)->patch("/admin/cargo/journeys/{$this->service->id}/status", ['status' => 'boarding'])->assertSessionHas('success');
        $this->assertSame('boarding', $this->service->fresh()->status);
    }

    public function test_handover_confirms_the_mobile_by_code_and_warns_without_blocking(): void
    {
        $c = $this->booking('delivered');

        $this->as($this->officer)->postJson("/admin/cargo/bookings/{$c->id}/handover/code", ['phone' => '0754999001'])
            ->assertOk()->assertJsonPath('sent', true);
        preg_match('/is (\d{6})\./', $this->sentTexts()->last()['text'], $m);
        $code = $m[1] ?? $this->fail('No code in the SMS');
        $this->assertSame('[hidden — contains a one-time code]', NotificationLog::where('event', 'cargo_collection_otp')->latest('id')->value('content'));

        $form = ['collector_name' => 'Halima Mussa', 'id_type' => 'national_id', 'id_number' => 'nida-777', 'phone' => '0754999001'];
        $wrong = $code === '000000' ? '111111' : '000000';
        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", $form + ['code' => $wrong])
            ->assertSessionHas('error', 'Wrong code (4 tries left).');
        $this->assertSame('delivered', $c->fresh()->status);

        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", $form + ['code' => $code])->assertSessionHas('success');

        $c->refresh();
        $this->assertSame('collected', $c->status);
        $this->assertNotNull($c->collected_at);
        $this->assertSame('Halima Mussa', $c->collected_by_name);
        $this->assertSame('NIDA-777', $c->collected_id_number);
        $this->assertSame('+255754999001', $c->collected_phone);
        $this->assertTrue($c->collected_phone_verified);
        $this->assertSame($this->officer->id, $c->handed_over_by);
        $this->assertCount(3, $c->collection_warnings, 'name, ID and mobile all differ from the booking');
        $this->assertTrue(AuditLog::where('action', 'cargo_handover')->where('entity_id', $c->id)->exists());
    }

    public function test_the_receiver_with_matching_details_has_no_warnings(): void
    {
        $c = $this->booking('unclaimed');
        $this->as($this->officer)->postJson("/admin/cargo/bookings/{$c->id}/handover/code", ['phone' => $c->receiver_phone])->assertOk();
        preg_match('/is (\d{6})\./', $this->sentTexts()->last()['text'], $m);

        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", [
            'collector_name' => 'juma receiver', 'id_type' => 'national_id', 'id_number' => 'NIDA-12345', 'phone' => $c->receiver_phone, 'code' => $m[1],
        ])->assertSessionHas('success');

        $this->assertNull($c->fresh()->collection_warnings);
    }

    public function test_when_the_code_cannot_be_delivered_a_reason_is_required(): void
    {
        $c = $this->booking('delivered');
        $this->fakeGateway(500);

        $this->as($this->officer)->postJson("/admin/cargo/bookings/{$c->id}/handover/code", ['phone' => $c->receiver_phone])
            ->assertOk()->assertJsonPath('sent', false);

        $form = ['collector_name' => 'Juma Receiver', 'id_type' => 'passport', 'id_number' => 'NIDA-12345', 'phone' => $c->receiver_phone];
        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", $form)->assertSessionHas('error');
        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", $form + ['unconfirmed_reason' => 'No network at the port'])
            ->assertSessionHas('success');

        $c->refresh();
        $this->assertSame('collected', $c->status);
        $this->assertFalse($c->collected_phone_verified);
        $this->assertStringContainsString('No network at the port', implode(' ', $c->collection_warnings));
    }

    public function test_five_wrong_codes_need_a_new_code_and_codes_go_only_to_arrived_cargo(): void
    {
        $c = $this->booking('delivered');
        $this->as($this->officer)->postJson("/admin/cargo/bookings/{$c->id}/handover/code", ['phone' => $c->receiver_phone]);
        preg_match('/is (\d{6})\./', $this->sentTexts()->last()['text'], $m);
        $wrong = $m[1] === '000000' ? '111111' : '000000';
        $form = ['collector_name' => 'Juma Receiver', 'id_type' => 'passport', 'id_number' => 'X1', 'phone' => $c->receiver_phone, 'code' => $wrong];

        for ($i = 0; $i < 5; $i++) {
            $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", $form);
        }
        $this->as($this->officer)->post("/admin/cargo/bookings/{$c->id}/handover", ['code' => $m[1]] + $form)
            ->assertSessionHas('error', 'Too many wrong codes. Send a new code.');

        $booked = $this->booking();
        $this->as($this->officer)->postJson("/admin/cargo/bookings/{$booked->id}/handover/code", ['phone' => $booked->receiver_phone])
            ->assertStatus(422)->assertJsonPath('message', 'Only cargo that has arrived can be handed over (this booking is booked).');
    }

    public function test_status_corrections_need_the_permission_and_a_reason(): void
    {
        $c = $this->booking('loaded');

        $this->as($this->officer)->patch("/admin/cargo/bookings/{$c->id}/status", ['status' => 'booked', 'reason' => 'Loaded by mistake'])->assertForbidden();
        $this->as($this->admin)->patch("/admin/cargo/bookings/{$c->id}/status", ['status' => 'booked', 'reason' => 'x'])->assertSessionHasErrors('reason');
        $this->as($this->admin)->patch("/admin/cargo/bookings/{$c->id}/status", ['status' => 'booked', 'reason' => 'Loaded by mistake'])->assertSessionHas('success');

        $this->assertSame('booked', $c->fresh()->status);
        $this->assertTrue(AuditLog::where('action', 'cargo_status_correct')->where('entity_id', $c->id)->exists());
    }

    public function test_the_portal_loads_and_hands_over_by_the_same_rules(): void
    {
        $c = $this->booking();
        $this->as($this->officer)->getJson("/portal/api/cargo/consignments/{$c->id}")->assertJsonPath('actions.can_load', true);
        $this->as($this->officer)->patchJson("/portal/api/cargo/consignments/{$c->id}/status", ['status' => 'in_transit'])->assertStatus(422);
        $this->as($this->officer)->patchJson("/portal/api/cargo/consignments/{$c->id}/status", ['status' => 'loaded'])->assertOk();
        $this->assertSame('loaded', $c->fresh()->status);

        $arrived = $this->booking('delivered');
        $this->as($this->officer)->getJson("/portal/api/cargo/consignments/{$arrived->id}")->assertJsonPath('actions.can_handover', true);
        $this->as($this->officer)->postJson("/portal/api/cargo/consignments/{$arrived->id}/handover/code", ['phone' => $arrived->receiver_phone])->assertOk();
        preg_match('/is (\d{6})\./', $this->sentTexts()->last()['text'], $m);
        $this->as($this->officer)->postJson("/portal/api/cargo/consignments/{$arrived->id}/handover", [
            'collector_name' => 'Juma Receiver', 'id_type' => 'national_id', 'id_number' => 'NIDA-12345', 'phone' => $arrived->receiver_phone, 'code' => $m[1],
        ])->assertOk()->assertJsonPath('phone_confirmed', true)->assertJsonPath('warnings', []);
        $this->assertSame('collected', $arrived->fresh()->status);
    }

    public function test_scanner_follows_the_rules_and_needs_port_permissions(): void
    {
        $c = $this->booking();
        [$first, $second] = $c->items()->get()->all();

        Sanctum::actingAs($this->officer);
        $this->postJson('/api/v1/scanner/cargo/status/update', ['type' => 'item', 'id' => $first->id, 'status' => 'loaded'])
            ->assertOk()->assertJsonPath('booking_loaded', false);
        $this->assertSame('booked', $c->fresh()->status);
        $this->postJson('/api/v1/scanner/cargo/status/update', ['type' => 'item', 'id' => $second->id, 'status' => 'loaded'])
            ->assertOk()->assertJsonPath('booking_loaded', true);
        $this->assertSame('loaded', $c->fresh()->status);
        $this->postJson('/api/v1/scanner/cargo/status/update', ['type' => 'consignment', 'id' => $c->id, 'status' => 'collected'])->assertStatus(422);

        $agent = User::where('domain', 'agent')->where('status', 'active')->first() ?? $this->markTestSkipped('Needs an agent.');
        Sanctum::actingAs($agent);
        $this->postJson('/api/v1/scanner/cargo/status/update', ['type' => 'consignment', 'id' => $c->id, 'status' => 'loaded'])->assertForbidden();
    }

    public function test_port_officer_reaches_journeys_and_manifest_but_not_corrections(): void
    {
        $this->assertTrue($this->officer->hasPermission('cargo-load') && $this->officer->hasPermission('cargo-collect'));
        $this->assertFalse($this->officer->hasPermission('cargo-status-correct'));
        $this->as($this->officer)->get('/admin/cargo/journeys?date=' . \Carbon\Carbon::parse($this->service->departure_date)->toDateString())
            ->assertOk()->assertSee('Depart');
        $this->as($this->officer)->get('/admin/cargo/manifest')->assertOk();
    }
}
