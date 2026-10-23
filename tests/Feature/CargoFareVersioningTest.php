<?php

namespace Tests\Feature;

use App\Models\CargoFare;
use App\Models\CargoInventory;
use App\Models\CargoItem;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Cargo fares as versions with a validity window, priced by booking time, changed through
 * preview + dual control, and the screens around them (rate card, type profile, route prices).
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoFareVersioningTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private User $checker;
    private TransportService $service;
    private CargoType $type;
    private CargoFare $fare;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $this->checker = User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->where('id', '!=', $this->admin->id)->first()
            ?? $this->markTestSkipped('Needs a second user who can approve.');
        $this->service = TransportService::availableForBooking()->whereHas('cargoInventory')->first()
            ?? $this->markTestSkipped('Needs a bookable departure with cargo capacity.');
        CargoInventory::where('transport_service_id', $this->service->id)->update([
            'total_weight_capacity_kg' => 100000, 'used_weight_kg' => 0, 'held_weight_kg' => 0,
            'total_volume_capacity_cbm' => 1000, 'used_volume_cbm' => 0, 'held_volume_cbm' => 0,
            'total_lane_meters' => 200, 'used_lane_meters' => 0, 'held_lane_meters' => 0,
        ]);
        SystemConfig::setValue('sms_enabled', '0'); // no customer SMS from tests
        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString());

        // Every fare and type action applies without approval unless a test switches a rule on
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'like', 'cargo_fare_%')->orWhere('operation_type', 'like', 'cargo_type_%')
            ->update(['requires_dual_control' => false]);

        $this->type = CargoType::create(['name' => 'Versioning test ' . Str::random(4), 'code' => 'VT' . strtoupper(Str::random(6)),
            'category' => 'vehicle', 'measurement_unit' => 'unit', 'lane_meters' => 0, 'status' => 'active']);
        $this->fare = CargoFare::create(['cargo_type_id' => $this->type->id, 'route_id' => $this->service->route_id,
            'price_tzs' => 50000, 'price_usd' => 0, 'status' => 'active', 'effective_from' => now()->subDay()]);
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function revision(array $overrides = []): array
    {
        return array_merge(['price_tzs' => 55000, 'value_charge_pct' => 0, 'reason' => 'Fuel levy increase'], $overrides);
    }

    public function test_screens_render(): void
    {
        $pages = [
            '/admin/cargo/fares', '/admin/cargo/types', "/admin/cargo/types/{$this->type->id}",
            "/admin/cargo/routes/{$this->service->route_id}", "/admin/cargo/transport-units/{$this->service->transport_unit_id}",
            '/admin/cargo/journeys-availability', "/admin/cargo/journeys/{$this->service->id}/availability",
            '/admin/dual-control', '/admin/dual-control?tab=all', '/admin/dual-control?tab=scheduled', '/admin/settings/dual-control-configs',
            '/admin/settings/exchange-rates', '/admin/settings/payment-channels', '/admin/settings/roles',
        ];
        foreach ($pages as $url) {
            $this->as($this->admin)->get($url)->assertOk();
        }

        foreach (['/admin/cargo/api/fares', '/admin/cargo/api/fares?state=history', '/admin/cargo/api/fares/rules',
            '/admin/cargo/api/fares/rate-card', '/admin/cargo/api/fares/rate-card?at=' . urlencode(now()->addMonth()->toDateTimeString()),
            "/admin/cargo/api/fares/series?cargo_type_id={$this->type->id}&route_id={$this->service->route_id}"] as $url) {
            $this->as($this->admin)->getJson($url)->assertOk();
        }
        $this->as($this->admin)->get('/admin/cargo/fares/export')->assertOk();
    }

    public function test_a_change_from_a_date_closes_the_current_fare_and_keeps_it(): void
    {
        $start = now()->addDays(3)->startOfDay();
        $body = $this->revision(['takes_effect' => 'at', 'takes_effect_at' => $start->format('Y-m-d\TH:i')]);

        $plan = $this->as($this->admin)->postJson('/admin/cargo/api/fares/preview', $body + ['kind' => 'revise', 'fare_id' => $this->fare->id])
            ->assertOk()->json();
        $alerts = collect($plan['series'][0]['alerts'])->pluck('text')->implode(' ');
        $this->assertStringContainsString('expires ' . $start->copy()->subMinute()->format('d M Y H:i'), $alerts);
        $this->assertStringContainsString('(+10%)', $alerts);

        $this->as($this->admin)->putJson("/admin/cargo/api/fares/{$this->fare->id}", $body + ['plan_hash' => $plan['hash']])
            ->assertOk()->assertJsonPath('applied', true);

        $this->assertTrue($this->fare->fresh()->effective_until->eq($start));
        $next = CargoFare::where('previous_fare_id', $this->fare->id)->firstOrFail();
        $this->assertSame(2, $next->version);
        $this->assertSame('scheduled', $next->state);
        $this->assertEquals(55000, (float) $next->price_tzs);
        $this->assertNotNull($next->dual_control_request_id);

        // Both versions in the history; the rate card shows the coming change
        $this->assertCount(2, $this->as($this->admin)->getJson("/admin/cargo/api/fares/series?cargo_type_id={$this->type->id}&route_id={$this->service->route_id}")->json());
        $cell = $this->as($this->admin)->getJson('/admin/cargo/api/fares/rate-card')->json('groups');
        $cell = collect($cell)->pluck('types')->flatten(1)->firstWhere('id', $this->type->id)['cells'][$this->service->route_id];
        $this->assertEquals(50000, $cell['fare']['rate']);
        $this->assertEquals(55000, $cell['next']['rate']);
    }

    public function test_a_stale_preview_is_refused_with_the_new_plan(): void
    {
        $plan = $this->as($this->admin)->postJson('/admin/cargo/api/fares/preview', $this->revision() + ['kind' => 'revise', 'fare_id' => $this->fare->id])->json();

        // Someone else schedules a change for the same fare meanwhile
        $this->fare->update(['effective_until' => now()->addDays(10)]);

        $this->as($this->admin)->putJson("/admin/cargo/api/fares/{$this->fare->id}", $this->revision() + ['plan_hash' => $plan['hash']])
            ->assertStatus(409)->assertJsonStructure(['message', 'plan' => ['hash', 'series']]);
    }

    public function test_a_fare_on_the_same_start_needs_replace_ticked(): void
    {
        $start = now()->addDays(5)->startOfDay();
        $this->fare->update(['effective_until' => $start]);
        CargoFare::create(['cargo_type_id' => $this->type->id, 'route_id' => $this->service->route_id, 'price_tzs' => 60000, 'status' => 'active', 'effective_from' => $start]);
        $body = ['cargo_type_id' => $this->type->id, 'route_id' => $this->service->route_id, 'price_tzs' => 65000, 'takes_effect' => 'at', 'takes_effect_at' => $start->format('Y-m-d\TH:i')];

        $this->as($this->admin)->postJson('/admin/cargo/api/fares', $body)->assertStatus(422)->assertJsonValidationErrors('replace');
        $this->as($this->admin)->postJson('/admin/cargo/api/fares', $body + ['replace' => true])->assertOk();

        $this->assertSame(1, CargoFare::where('cargo_type_id', $this->type->id)->where('status', CargoFare::CANCELLED)->count());
        $this->assertEquals(65000, (float) CargoFare::where('cargo_type_id', $this->type->id)->scheduled()->value('price_tzs'));
    }

    public function test_revoke_ends_the_fare_now_and_keeps_it(): void
    {
        $this->as($this->admin)->postJson("/admin/cargo/api/fares/{$this->fare->id}/end", ['end_mode' => 'now', 'revoke' => true, 'reason' => 'Configured with the wrong price'])
            ->assertOk();

        $fare = $this->fare->fresh();
        $this->assertSame(CargoFare::REVOKED, $fare->status);
        $this->assertFalse($fare->effective_until->isFuture());
        $this->assertNull(app(\App\Services\Cargo\CargoFareCalculator::class)->resolveFare($this->type->id, $this->service->route_id, $this->service->transport_unit_id));
    }

    public function test_bookings_are_priced_at_the_moment_space_was_held_and_keep_a_copy(): void
    {
        $items = [['cargo_type_id' => $this->type->id, 'quantity' => 2]];
        $lockId = $this->as($this->admin)->postJson('/admin/cargo/api/booking/lock-inventory', ['transport_service_id' => $this->service->id, 'items' => $items])
            ->assertOk()->json('lock_id');

        // The price changes after the clerk held space
        Carbon::setTestNow(now()->addSeconds(30));
        $this->as($this->admin)->putJson("/admin/cargo/api/fares/{$this->fare->id}", $this->revision(['price_tzs' => 80000]))->assertOk();

        $res = $this->as($this->admin)->postJson('/admin/cargo/api/booking/confirm', [
            'lock_id' => $lockId, 'transport_service_id' => $this->service->id, 'items' => $items, 'payment_method' => 'cash',
            'sender_name' => 'Hold Test', 'sender_phone' => '0754111999', 'receiver_name' => 'Price Test', 'receiver_phone' => '0754111888',
        ])->assertCreated();

        $consignment = Consignment::findOrFail($res->json('consignment.id'));
        $this->assertEquals(100000, (float) $consignment->total_amount_tzs); // 2 × 50,000, the price when space was held
        $item = CargoItem::where('consignment_id', $consignment->id)->firstOrFail();
        $this->assertSame($this->fare->id, $item->fare_id);
        $this->assertSame(1, $item->fare_version);
        $this->assertEquals(50000, (float) $item->unit_rate);
        $this->assertSame($this->fare->id, $item->pricing_snapshot['fare']['id']);
        $this->assertEquals(50000, $item->pricing_snapshot['rates']['price_tzs']);
        $this->assertNotNull($item->priced_at);

        // A new quote uses the new price
        $this->as($this->admin)->postJson('/admin/cargo/api/booking/quote', ['transport_service_id' => $this->service->id, 'items' => $items])
            ->assertOk()->assertJsonPath('net_amount', 160000);
        Carbon::setTestNow();
    }

    public function test_departures_list_only_the_cargo_types_that_have_a_fare(): void
    {
        $unpriced = CargoType::create(['name' => 'No fare ' . Str::random(4), 'code' => 'NF' . strtoupper(Str::random(6)),
            'category' => 'general', 'measurement_unit' => 'unit', 'status' => 'active']);

        $services = $this->as($this->admin)->getJson('/admin/cargo/api/booking/available-services?date=' . $this->service->departure_date)->assertOk()->json();
        $priced = collect($services)->firstWhere('id', $this->service->id)['priced_type_ids'];

        $this->assertContains($this->type->id, $priced);
        $this->assertNotContains($unpriced->id, $priced);
    }

    public function test_a_fare_without_a_rate_for_its_pricing_basis_is_not_bookable(): void
    {
        // Legacy shape: a per-cbm type whose fare only has a flat price
        $noRate = CargoType::create(['name' => 'No rate ' . Str::random(4), 'code' => 'NR' . strtoupper(Str::random(6)),
            'category' => 'electronics', 'measurement_unit' => 'cbm', 'status' => 'active']);
        CargoFare::create(['cargo_type_id' => $noRate->id, 'route_id' => $this->service->route_id,
            'price_tzs' => 50000, 'price_usd' => 0, 'status' => 'active', 'effective_from' => now()->subDay()]);

        $services = $this->as($this->admin)->getJson('/admin/cargo/api/booking/available-services?date=' . $this->service->departure_date)->assertOk()->json();
        $priced = collect($services)->firstWhere('id', $this->service->id)['priced_type_ids'];
        $this->assertContains($this->type->id, $priced);
        $this->assertNotContains($noRate->id, $priced);

        // The server refuses the line too, instead of pricing it at 0
        $this->as($this->admin)->postJson('/admin/cargo/api/booking/quote', ['transport_service_id' => $this->service->id,
            'items' => [['cargo_type_id' => $noRate->id, 'quantity' => 1, 'length_m' => 1, 'width_m' => 1, 'height_m' => 1]]])
            ->assertStatus(422)->assertJsonValidationErrors(['items.0.cargo_type_id']);

        // A vessel fare with no rate hides the type on that vessel even when the route-wide fare is priced
        CargoFare::create(['cargo_type_id' => $this->type->id, 'route_id' => $this->service->route_id, 'transport_unit_id' => $this->service->transport_unit_id,
            'price_tzs' => 0, 'price_usd' => 0, 'status' => 'active', 'effective_from' => now()->subDay()]);
        $this->assertNotContains($this->type->id, app(\App\Services\Cargo\CargoFareCalculator::class)
            ->pricedTypeIds($this->service->route_id, $this->service->transport_unit_id));
    }

    public function test_with_the_rule_on_a_fare_waits_for_a_checker_and_starts_as_requested(): void
    {
        DualControlConfig::where('operation_type', 'cargo_fare_create')->update(['requires_dual_control' => true]);
        $other = CargoType::create(['name' => 'Approval test ' . Str::random(4), 'code' => 'AT' . strtoupper(Str::random(6)),
            'category' => 'general', 'measurement_unit' => 'unit', 'status' => 'active']);
        $start = now()->addDays(2)->startOfDay();

        $res = $this->as($this->admin)->postJson('/admin/cargo/api/fares', [
            'cargo_type_id' => $other->id, 'route_id' => $this->service->route_id, 'price_tzs' => 9000,
            'takes_effect' => 'at', 'takes_effect_at' => $start->format('Y-m-d\TH:i'),
        ])->assertStatus(202)->assertJsonPath('status', 'pending');
        $this->assertSame(0, CargoFare::where('cargo_type_id', $other->id)->count());

        // A second change to the same fare is refused while the first is open
        $this->as($this->admin)->postJson('/admin/cargo/api/fares/preview', ['kind' => 'create', 'cargo_type_id' => $other->id, 'route_id' => $this->service->route_id, 'price_tzs' => 9500])
            ->assertOk()->assertJsonPath('blocking.0', fn ($m) => str_contains($m, 'awaiting approval'));

        // The maker can't approve their own request; another person can
        $request = DualControlRequest::findOrFail($res->json('request_id'));
        $this->as($this->admin)->post("/admin/dual-control/{$request->id}/approve")->assertForbidden();
        $this->as($this->checker)->post("/admin/dual-control/{$request->id}/approve")->assertRedirect();

        $fare = CargoFare::where('cargo_type_id', $other->id)->firstOrFail();
        $this->assertTrue($fare->effective_from->eq($start));
        $this->assertSame($request->id, $fare->dual_control_request_id);
        $this->assertSame('approved', $request->fresh()->status);
        $this->as($this->admin)->get("/admin/dual-control/{$request->id}")->assertOk()->assertSee('Fare change');
    }

    public function test_capacity_cannot_drop_below_what_is_booked(): void
    {
        CargoInventory::where('transport_service_id', $this->service->id)->update(['used_weight_kg' => 500]);
        $url = "/admin/cargo/journeys/{$this->service->id}/availability";

        $this->as($this->admin)->post($url, ['lane_meters' => 100, 'weight_kg' => 400, 'volume_cbm' => 50])->assertSessionHasErrors('weight_kg');

        DualControlConfig::where('operation_type', 'cargo_capacity_update')->update(['requires_dual_control' => false]);
        $this->as($this->admin)->post($url, ['lane_meters' => 100, 'weight_kg' => 600, 'volume_cbm' => 50])->assertRedirect();
        $this->assertEquals(600, (float) CargoInventory::where('transport_service_id', $this->service->id)->value('total_weight_capacity_kg'));
    }

    public function test_cargo_type_changes_go_through_dual_control(): void
    {
        $body = ['name' => 'Renamed ' . Str::random(4), 'category' => 'vehicle', 'measurement_unit' => 'unit', 'lane_meters' => 5, 'max_weight_kg' => 3000, 'status' => 'active'];
        $this->as($this->admin)->putJson("/admin/cargo/api/types/{$this->type->id}", $body)->assertOk()->assertJsonPath('applied', true);
        $this->assertSame($body['name'], $this->type->fresh()->name);

        DualControlConfig::where('operation_type', 'cargo_type_update')->update(['requires_dual_control' => true]);
        $this->as($this->admin)->putJson("/admin/cargo/api/types/{$this->type->id}", ['name' => 'Waits for approval'] + $body)->assertStatus(202);
        $this->assertSame($body['name'], $this->type->fresh()->name);

        // A type with fares can't be deleted
        $this->as($this->admin)->deleteJson("/admin/cargo/api/types/{$this->type->id}")->assertStatus(422);
    }
}
