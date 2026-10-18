<?php

namespace Tests\Feature;

use App\Models\CargoFare;
use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\Role;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Admin cargo screens: dashboard, bookings list, manifest, reports, fares and the booking terminal API.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoAdminScreensTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first()
            ?? User::whereHas('roles', fn ($q) => $q->where('slug', 'super_admin'))->first()
            ?? $this->markTestSkipped('Needs an admin user in the database.');

        SystemConfig::setValue('sms_enabled', '0'); // no customer SMS from tests
    }

    private function asAdmin()
    {
        return $this->actingAs($this->admin)->withSession(['2fa_verified' => true]);
    }

    public function test_pages_render(): void
    {
        $consignment = Consignment::first();
        $service = TransportService::whereHas('cargoInventory')->first();

        foreach ([
            '/admin/cargo/dashboard',
            '/admin/cargo/consignments?status=booked&payment_status=unpaid',
            '/admin/cargo/manifest' . ($service ? '?service=' . $service->id : ''),
            '/admin/cargo/booking',
            '/admin/cargo/fares',
            '/admin/reports',
        ] as $url) {
            $this->asAdmin()->get($url)->assertOk();
        }

        if ($consignment) {
            $this->asAdmin()->get("/admin/cargo/bookings/{$consignment->id}")->assertOk();
        }
    }

    public function test_dashboard_api_for_every_period(): void
    {
        foreach (['today', '7d', '30d', 'month'] as $period) {
            $this->asAdmin()->getJson("/admin/cargo/api/dashboard?period={$period}")
                ->assertOk()
                ->assertJsonStructure(['kpis' => ['bookings', 'collected', 'avg_value', 'weight_kg', 'cancellation_rate'],
                    'outstanding', 'trend' => ['labels', 'bookings', 'collected'], 'by_channel', 'top_routes', 'pipeline', 'departures', 'attention', 'recent']);
        }
    }

    public function test_bookings_list_filters_search_and_export(): void
    {
        $c = Consignment::first() ?? $this->markTestSkipped('Needs a consignment.');

        $this->asAdmin()->getJson('/admin/cargo/api/consignments?search=' . urlencode(strtolower($c->sender_name)))
            ->assertOk()->assertJsonPath('data.0.id', fn ($id) => $id !== null)->assertJsonStructure(['status_counts']);

        $digits = substr(preg_replace('/\D/', '', (string) $c->sender_phone), -9);
        if (strlen($digits) === 9) {
            $ids = collect($this->asAdmin()->getJson('/admin/cargo/api/consignments?per_page=100&search=255' . $digits)->json('data'))->pluck('id');
            $this->assertTrue($ids->contains($c->id), 'Phone search in +255 format finds the booking');
        }

        $this->asAdmin()->getJson('/admin/cargo/api/consignments?date_field=departure&date_from=2000-01-01&route_id=' . $c->route_id)->assertOk();
        $this->asAdmin()->get('/admin/cargo/consignments/export?status=booked')->assertOk();
    }

    public function test_every_report_renders_and_exports(): void
    {
        foreach (['sales', 'register', 'collections', 'capacity', 'cargo_mix', 'cancellations', 'aging'] as $report) {
            $this->asAdmin()->get("/admin/reports/{$report}?date_from=2026-01-01&date_to=2026-12-31")->assertOk();
            $this->asAdmin()->get("/admin/reports/{$report}?date_from=2026-01-01&date_to=2026-12-31&export=excel")->assertOk();
        }
        foreach (['week', 'route', 'channel', 'staff'] as $group) {
            $this->asAdmin()->get("/admin/reports/sales?group_by={$group}")->assertOk();
        }
        $this->asAdmin()->get('/admin/reports/sales?export=pdf')->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->asAdmin()->get('/admin/reports/nope')->assertNotFound();
    }

    public function test_manifest_api_and_exports(): void
    {
        $service = TransportService::whereHas('consignments')->first() ?? $this->markTestSkipped('Needs a departure with bookings.');

        $this->asAdmin()->getJson('/admin/cargo/api/manifest-services?date=' . $service->departure_date)->assertOk()->assertJsonFragment(['id' => $service->id]);
        $this->asAdmin()->getJson("/admin/cargo/api/manifest/{$service->id}")->assertOk()->assertJsonStructure(['route', 'consignments', 'by_type', 'awaiting_payment']);
        $this->asAdmin()->get("/admin/cargo/manifest/{$service->id}/export?format=pdf")->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->asAdmin()->get("/admin/cargo/manifest/{$service->id}/export?format=excel")->assertOk();
    }

    public function test_fare_validation(): void
    {
        $route = TransportService::first()->route_id;
        $type = CargoType::create(['name' => 'Fare test', 'code' => 'FT' . strtoupper(Str::random(6)), 'category' => 'general', 'measurement_unit' => 'kg', 'status' => 'active']);

        // Route is required; a kg type needs a per-kg rate
        $this->asAdmin()->postJson('/admin/cargo/api/fares', ['cargo_type_id' => $type->id, 'price_tzs' => 50000, 'status' => 'active'])
            ->assertStatus(422)->assertJsonValidationErrors('route_id');
        $this->asAdmin()->postJson('/admin/cargo/api/fares', ['cargo_type_id' => $type->id, 'route_id' => $route, 'price_tzs' => 50000, 'status' => 'active'])
            ->assertStatus(422)->assertJsonValidationErrors('price_per_kg_tzs');

        $ok = ['cargo_type_id' => $type->id, 'route_id' => $route, 'price_per_kg_tzs' => 1200];
        $this->asAdmin()->postJson('/admin/cargo/api/fares', $ok)->assertOk()->assertJsonPath('applied', true);

        // The same fare again is refused: it is already in place
        $this->assertStringContainsString('already in place', $this->asAdmin()->postJson('/admin/cargo/api/fares', $ok)->assertStatus(422)->json('message'));

        // A fare that has started keeps its history: it is ended, not deleted
        $fare = CargoFare::where('cargo_type_id', $type->id)->first();
        $this->asAdmin()->deleteJson("/admin/cargo/api/fares/{$fare->id}", [])->assertStatus(422);
        $this->assertNotNull($fare->fresh());
    }

    public function test_terminal_books_at_the_server_price_with_cash(): void
    {
        $service = TransportService::availableForBooking()->whereHas('cargoInventory')->first()
            ?? $this->markTestSkipped('Needs a bookable departure with cargo inventory.');
        CargoInventory::where('transport_service_id', $service->id)->update([
            'total_weight_capacity_kg' => 10000, 'used_weight_kg' => 0, 'held_weight_kg' => 0,
            'total_volume_capacity_cbm' => 100, 'used_volume_cbm' => 0, 'held_volume_cbm' => 0,
            'total_lane_meters' => 50, 'used_lane_meters' => 0, 'held_lane_meters' => 0,
        ]);
        $type = CargoType::create(['name' => 'Terminal test', 'code' => 'TT' . strtoupper(Str::random(6)), 'category' => 'general', 'measurement_unit' => 'kg', 'max_weight_kg' => 500, 'status' => 'active']);
        CargoFare::create(['cargo_type_id' => $type->id, 'route_id' => $service->route_id, 'price_tzs' => 0, 'price_usd' => 0, 'price_per_kg_tzs' => 1000, 'status' => 'active']);

        // A client-sent price is ignored
        $items = [['cargo_type_id' => $type->id, 'quantity' => 2, 'weight_kg' => 40, 'fare_amount' => 1, 'description' => 'Rice']];

        $this->asAdmin()->getJson('/admin/cargo/api/booking/available-services?date=' . $service->departure_date)
            ->assertOk()->assertJsonFragment(['id' => $service->id]);

        $this->asAdmin()->postJson('/admin/cargo/api/booking/quote', ['transport_service_id' => $service->id, 'items' => $items])
            ->assertOk()->assertJsonPath('net_amount', 40000);

        $lockId = $this->asAdmin()->postJson('/admin/cargo/api/booking/lock-inventory', ['transport_service_id' => $service->id, 'items' => $items])
            ->assertOk()->json('lock_id');

        $res = $this->asAdmin()->postJson('/admin/cargo/api/booking/confirm', [
            'lock_id' => $lockId, 'transport_service_id' => $service->id, 'items' => $items,
            'sender_name' => 'Asha Test', 'sender_phone' => '+255 754 111 222',
            'receiver_name' => 'Juma Test', 'receiver_phone' => '0655 333 444',
            'special_instructions' => 'Keep dry', 'payment_method' => 'cash',
        ])->assertCreated();

        $c = Consignment::findOrFail($res->json('consignment.id'));
        $this->assertEquals(40000, (float) $c->total_amount_tzs);
        $this->assertEquals(40, (float) $c->total_weight_kg);
        $this->assertSame('0754111222', $c->sender_phone);
        $this->assertSame('Keep dry', $c->special_instructions);
        $this->assertSame('paid', $c->payment_status);
        $this->assertEquals(40, (float) CargoInventory::where('transport_service_id', $service->id)->value('used_weight_kg'));
        $this->assertNull(CargoLock::find($lockId));

        // The booking shows up for customer autofill by phone
        $this->asAdmin()->getJson('/admin/cargo/api/booking/customers?phone=0754111222')->assertOk()->assertJsonFragment(['name' => 'Asha Test']);
    }

    public function test_sales_portal_books_on_the_same_engine(): void
    {
        $officer = User::whereHas('roles', fn ($q) => $q->where('slug', 'sales_officer'))->first()
            ?? $this->markTestSkipped('Needs a sales officer user.');
        $service = TransportService::availableForBooking()->whereHas('cargoInventory')->first()
            ?? $this->markTestSkipped('Needs a bookable departure.');
        CargoInventory::where('transport_service_id', $service->id)->update(['total_weight_capacity_kg' => 10000, 'used_weight_kg' => 0, 'held_weight_kg' => 0]);
        $type = CargoType::create(['name' => 'Portal test', 'code' => 'PO' . strtoupper(Str::random(6)), 'category' => 'general', 'measurement_unit' => 'unit', 'status' => 'active']);
        CargoFare::create(['cargo_type_id' => $type->id, 'route_id' => $service->route_id, 'price_tzs' => 7000, 'price_usd' => 0, 'status' => 'active']);
        // A client-sent discount is ignored
        $items = [['cargo_type_id' => $type->id, 'quantity' => 3, 'weight_kg' => 9, 'discount_amount' => 20000]];
        $portal = fn () => $this->actingAs($officer)->withSession(['2fa_verified' => true]);

        $portal()->postJson('/portal/api/cargo/calculate-fare', ['transport_service_id' => $service->id, 'currency' => 'TZS', 'items' => $items])
            ->assertOk()->assertJsonPath('net_amount', 21000);
        $lockId = $portal()->postJson('/portal/api/cargo/lock-inventory', ['transport_service_id' => $service->id, 'items' => $items])->assertOk()->json('lock_id');
        $id = $portal()->postJson('/portal/api/cargo/confirm-booking', [
            'lock_id' => $lockId, 'items' => $items, 'currency' => 'TZS', 'payment_method' => 'cash',
            'sender_name' => 'Portal Sender', 'sender_phone' => '0754000111', 'receiver_name' => 'Portal Receiver', 'receiver_phone' => '0754000222',
        ])->assertOk()->json('consignment_id');

        $c = Consignment::findOrFail($id);
        $this->assertEquals(21000, (float) $c->total_amount_tzs);
        $this->assertEquals(3, $c->total_items);
        $this->assertMatchesRegularExpression('/^CG-\d{6}-[A-Z2-9]{5}$/', $c->consignment_number);
    }

    public function test_terminal_rejects_bad_phone_and_unauthorised_discount(): void
    {
        $service = TransportService::availableForBooking()->whereHas('cargoInventory')->first()
            ?? $this->markTestSkipped('Needs a bookable departure.');
        $type = CargoType::create(['name' => 'Phone test', 'code' => 'PT' . strtoupper(Str::random(6)), 'category' => 'general', 'measurement_unit' => 'unit', 'status' => 'active']);
        CargoFare::create(['cargo_type_id' => $type->id, 'route_id' => $service->route_id, 'price_tzs' => 5000, 'price_usd' => 0, 'status' => 'active']);
        $items = [['cargo_type_id' => $type->id]];

        $this->asAdmin()->postJson('/admin/cargo/api/booking/confirm', [
            'lock_id' => 0, 'transport_service_id' => $service->id, 'items' => $items,
            'sender_name' => 'A', 'sender_phone' => '12345', 'receiver_name' => 'B', 'receiver_phone' => '0754000000', 'payment_method' => 'cash',
        ])->assertStatus(422)->assertJsonValidationErrors('sender_phone');

        // Discount without a reason
        $this->asAdmin()->postJson('/admin/cargo/api/booking/quote', ['transport_service_id' => $service->id, 'items' => $items, 'discount_amount' => 1000])
            ->assertStatus(422)->assertJsonValidationErrors('discount_reason');

        // A clerk without cargo-override-fare can't discount
        $clerk = User::forceCreate([
            'email' => 'cargo-clerk-' . Str::random(8) . '@example.test', 'password' => bcrypt(Str::random(16)),
            'first_name' => 'Test', 'last_name' => 'Clerk', 'domain' => 'system_user', 'status' => 'active',
            'password_changed_at' => now(),
        ]);
        $role = Role::firstOrCreate(['slug' => 'cargo_test_clerk'], ['name' => 'Cargo test clerk', 'display_name' => 'Cargo test clerk']);
        $role->permissions()->sync(\App\Models\Permission::whereIn('name', ['admin_portal:access', 'cargo-book', 'cargo-view'])->pluck('id'));
        $clerk->roles()->sync([$role->id]);

        $this->actingAs($clerk)->withSession(['2fa_verified' => true])
            ->postJson('/admin/cargo/api/booking/quote', ['transport_service_id' => $service->id, 'items' => $items, 'discount_amount' => 1000, 'discount_reason' => 'Regular customer'])
            ->assertForbidden();
        $this->actingAs($clerk)->withSession(['2fa_verified' => true])->getJson('/admin/cargo/api/fares')->assertForbidden();
        $this->actingAs($clerk)->withSession(['2fa_verified' => true])->get('/admin/reports')->assertForbidden();
    }
}
