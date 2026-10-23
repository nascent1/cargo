<?php

namespace Tests\Feature;

use App\Models\CargoFare;
use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\CargoType;
use App\Models\Consignment;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Route;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Sales portal booking: the same flow and rules as the admin booking terminal, plus the
 * counter's own-day dashboard and the value charge.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoPortalBookingTest extends TestCase
{
    use DatabaseTransactions;

    private User $officer;
    private User $agent;
    private TransportService $service;
    private CargoType $parcel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = TransportService::availableForBooking()->whereHas('cargoInventory')->first()
            ?? $this->markTestSkipped('Needs a bookable departure with cargo capacity.');
        CargoInventory::where('transport_service_id', $this->service->id)->update([
            'total_weight_capacity_kg' => 100000, 'used_weight_kg' => 0, 'held_weight_kg' => 0,
            'total_volume_capacity_cbm' => 1000, 'used_volume_cbm' => 0, 'held_volume_cbm' => 0,
            'total_lane_meters' => 200, 'used_lane_meters' => 0, 'held_lane_meters' => 0,
        ]);

        $this->officer = $this->user('system_user', 'sales_officer');
        $this->agent = $this->user('agent', 'agent');

        $this->parcel = $this->type(['measurement_unit' => 'unit']);
        $this->fare($this->parcel, ['price_tzs' => 7000]);

        SystemConfig::setValue('sms_enabled', '0'); // no customer SMS from tests
        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString()); // admins are asked for the rate when it's due
    }

    private function user(string $domain, string $roleSlug): User
    {
        $user = User::forceCreate([
            'email' => "portal-{$roleSlug}-" . Str::random(8) . '@example.test', 'password' => bcrypt(Str::random(16)),
            'first_name' => 'Portal', 'last_name' => ucfirst($roleSlug), 'domain' => $domain, 'status' => 'active',
            'password_changed_at' => now(),
        ]);
        $role = Role::where('slug', $roleSlug)->first() ?? $this->markTestSkipped("Needs the {$roleSlug} role.");
        $user->roles()->sync([$role->id]);

        return $user;
    }

    private function type(array $attrs = []): CargoType
    {
        return CargoType::create(array_merge([
            'name' => 'Portal test ' . Str::random(4), 'code' => 'PB' . strtoupper(Str::random(6)),
            'category' => 'general', 'measurement_unit' => 'unit', 'lane_meters' => 0, 'status' => 'active',
        ], $attrs));
    }

    private function fare(CargoType $type, array $attrs = []): CargoFare
    {
        return CargoFare::create(array_merge([
            'cargo_type_id' => $type->id, 'route_id' => $this->service->route_id, 'price_tzs' => 0, 'price_usd' => 0, 'status' => 'active',
        ], $attrs));
    }

    private function as(User $user)
    {
        return $this->actingAs($user)->withSession(['2fa_verified' => true]);
    }

    private function hold(User $user, array $items, ?int $replace = null): int
    {
        return $this->as($user)->postJson('/portal/api/cargo/lock-inventory', [
            'transport_service_id' => $this->service->id, 'items' => $items, 'replace_lock_id' => $replace,
        ])->assertOk()->json('lock_id');
    }

    private function book(User $user, array $items, array $extra = []): int
    {
        $lockId = $this->hold($user, $items);

        return $this->as($user)->postJson('/portal/api/cargo/confirm-booking', array_merge([
            'lock_id' => $lockId, 'transport_service_id' => $this->service->id, 'items' => $items, 'payment_method' => 'cash',
            'sender_name' => 'Neema Sender', 'sender_phone' => '0754 000 111', 'receiver_name' => 'Juma Receiver', 'receiver_phone' => '+255 655 000 222',
        ], $extra))->assertOk()->json('consignment_id');
    }

    public function test_departures_cover_the_next_days_with_space_and_skip_inactive_routes(): void
    {
        $list = collect($this->as($this->officer)->getJson('/portal/api/cargo/availability?days=3')->assertOk()->json());

        $this->assertTrue($list->contains('id', $this->service->id));
        $this->assertTrue($list->every(fn ($s) => $s['departure_date'] >= today()->toDateString() && $s['departure_date'] <= today()->addDays(2)->toDateString()));
        $mine = $list->firstWhere('id', $this->service->id);
        $this->assertSame(['pct', 'dimension', 'free', 'full'], array_keys($mine['space']));
        $this->assertFalse($mine['space']['full']);

        // A departure on an inactive route can't be booked
        Route::whereKey($this->service->route_id)->update(['status' => 'inactive']);
        $this->assertFalse(collect($this->as($this->officer)->getJson('/portal/api/cargo/availability?days=3')->json())->contains('id', $this->service->id));
    }

    public function test_quote_reports_errors_per_line_and_adds_the_value_charge(): void
    {
        $tv = $this->type();
        $this->fare($tv, ['price_tzs' => 7000, 'value_charge_pct' => 2]);
        $quote = fn (array $items) => $this->as($this->officer)->postJson('/portal/api/cargo/calculate-fare', ['transport_service_id' => $this->service->id, 'items' => $items]);

        $quote([['cargo_type_id' => $this->parcel->id], ['cargo_type_id' => $tv->id]])
            ->assertStatus(422)->assertJsonValidationErrors('items.1.declared_value')->assertJsonMissingValidationErrors('items.0.cargo_type_id');

        $quote([['cargo_type_id' => $tv->id, 'declared_value' => 100000]])
            ->assertOk()->assertJsonPath('items.0.value_charge', 2000)->assertJsonPath('net_amount', 9000);
    }

    public function test_a_hold_is_replaced_not_stacked_and_only_its_owner_can_use_it(): void
    {
        $items = [['cargo_type_id' => $this->parcel->id, 'quantity' => 2, 'weight_kg' => 40]];
        $first = $this->hold($this->officer, $items);
        $second = $this->hold($this->officer, $items, $first);

        $this->assertNull(CargoLock::find($first));
        $this->assertEquals(40, (float) CargoInventory::where('transport_service_id', $this->service->id)->value('held_weight_kg'));

        // Another clerk can neither release nor book against it
        $this->as($this->agent)->postJson('/portal/api/cargo/release-lock', ['lock_id' => $second])->assertOk();
        $this->assertNotNull(CargoLock::find($second));
        $this->as($this->agent)->postJson('/portal/api/cargo/confirm-booking', [
            'lock_id' => $second, 'items' => $items, 'payment_method' => 'cash',
            'sender_name' => 'X', 'sender_phone' => '0754000111', 'receiver_name' => 'Y', 'receiver_phone' => '0754000222',
        ])->assertStatus(422)->assertJsonPath('message', 'The space hold has expired. Go back to the cargo step to hold space again.');

        $this->as($this->officer)->postJson('/portal/api/cargo/release-lock', ['lock_id' => $second])->assertOk();
        $this->assertNull(CargoLock::find($second));
    }

    public function test_booking_normalises_phones_and_keeps_vehicle_and_value_details(): void
    {
        $car = $this->type(['category' => 'vehicle', 'measurement_unit' => 'lane_meter', 'lane_meters' => 4.5]);
        $this->fare($car, ['price_tzs' => 50000, 'value_charge_pct' => 1]);
        $items = [['cargo_type_id' => $car->id, 'declared_value' => 2000000]];

        // A vehicle needs its registration number
        $lockId = $this->hold($this->officer, $items);
        $this->as($this->officer)->postJson('/portal/api/cargo/confirm-booking', [
            'lock_id' => $lockId, 'items' => $items, 'payment_method' => 'cash',
            'sender_name' => 'A', 'sender_phone' => '0754000111', 'receiver_name' => 'B', 'receiver_phone' => '0754000222',
        ])->assertStatus(422)->assertJsonValidationErrors('items.0.vehicle_reg_number');

        $items[0] += ['vehicle_reg_number' => 't 482 dkv', 'driver_name' => 'Juma', 'driver_phone' => '0713 000 999'];
        $id = $this->book($this->officer, $items, ['receiver_id_type' => 'NIDA', 'receiver_id_number' => '1990', 'special_instructions' => 'Call on arrival']);

        $c = Consignment::with('items')->findOrFail($id);
        $this->assertSame('0754000111', $c->sender_phone);
        $this->assertSame('0655000222', $c->receiver_phone);
        $this->assertSame('NIDA', $c->receiver_id_type);
        $this->assertSame('Call on arrival', $c->special_instructions);
        $this->assertEquals(70000, (float) $c->total_amount_tzs);
        $this->assertSame('T 482 DKV', $c->items[0]->vehicle_reg_number);
        $this->assertSame('0713000999', $c->items[0]->driver_phone);
        $this->assertEquals(20000, (float) $c->items[0]->value_charge);
        $this->assertEquals(4.5, (float) $c->items[0]->lane_meters_consumed);
    }

    public function test_booking_endpoints_need_cargo_book(): void
    {
        $viewer = User::forceCreate([
            'email' => 'portal-viewer-' . Str::random(8) . '@example.test', 'password' => bcrypt(Str::random(16)),
            'first_name' => 'Portal', 'last_name' => 'Viewer', 'domain' => 'system_user', 'status' => 'active', 'password_changed_at' => now(),
        ]);
        $role = Role::firstOrCreate(['slug' => 'cargo_test_portal_viewer'], ['name' => 'Portal viewer', 'display_name' => 'Portal viewer']);
        $role->permissions()->sync(Permission::whereIn('name', ['portal:access', 'booking:read'])->pluck('id'));
        $viewer->roles()->sync([$role->id]);

        $items = [['cargo_type_id' => $this->parcel->id]];
        $this->as($viewer)->postJson('/portal/api/cargo/calculate-fare', ['transport_service_id' => $this->service->id, 'items' => $items])->assertForbidden();
        $this->as($viewer)->postJson('/portal/api/cargo/lock-inventory', ['transport_service_id' => $this->service->id, 'items' => $items])->assertForbidden();
        $this->as($viewer)->getJson('/portal/api/cargo/customers?phone=0754000111')->assertForbidden();
        $this->as($viewer)->getJson('/portal/api/cargo/availability?days=3')->assertOk();
    }

    public function test_dashboard_shows_only_the_clerks_own_day(): void
    {
        $id = $this->book($this->officer, [['cargo_type_id' => $this->parcel->id, 'quantity' => 2]]);

        $mine = $this->as($this->officer)->getJson('/portal/api/cargo/dashboard-summary')->assertOk()
            ->assertJsonStructure(['tiles' => ['bookings', 'collected', 'awaiting'], 'departures', 'action', 'recent']);
        $this->assertEquals(1, $mine->json('tiles.bookings.today'));
        $this->assertEquals(14000, $mine->json('tiles.collected.cash'));
        $this->assertSame($id, $mine->json('recent.0.id'));

        $theirs = $this->as($this->agent)->getJson('/portal/api/cargo/dashboard-summary')->assertOk();
        $this->assertEquals(0, $theirs->json('tiles.bookings.today'));
        $this->assertEquals(0, $theirs->json('tiles.collected.total'));
        $this->assertSame([], $theirs->json('recent'));
    }

    public function test_customer_lookup_and_bookings_list_respect_who_booked(): void
    {
        $this->book($this->officer, [['cargo_type_id' => $this->parcel->id]], ['sender_phone' => '0787 123 456', 'sender_name' => 'Fatma Lookup']);

        $this->as($this->officer)->getJson('/portal/api/cargo/customers?phone=+255787123456')->assertOk()->assertJsonFragment(['name' => 'Fatma Lookup']);
        // Agents never see another clerk's customers or bookings
        $this->as($this->agent)->getJson('/portal/api/cargo/customers?phone=0787123456')->assertOk()->assertExactJson([]);
        $this->assertSame(0, $this->as($this->agent)->getJson('/portal/api/cargo/consignments?search=0787123456')->json('total'));

        // Phone search in any format, narrowed to the clerk's own bookings
        $this->assertSame(1, $this->as($this->officer)->getJson('/portal/api/cargo/consignments?mine=1&search=' . urlencode('+255 787 123 456'))->json('total'));
        $this->assertSame(1, $this->as($this->officer)->getJson('/portal/api/cargo/consignments?mine=1&search=' . urlencode('0787 123'))->json('total'));
        $this->assertSame(1, $this->as($this->officer)->getJson('/portal/api/cargo/consignments?mine=1&search=' . urlencode('+255 78712'))->json('total'));
    }

    public function test_value_charge_can_be_set_on_the_filtered_fares_at_once(): void
    {
        $admin = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['admin', 'super_admin']))->first()
            ?? $this->markTestSkipped('Needs an admin user.');
        $other = $this->type();
        $otherFare = $this->fare($other, ['price_tzs' => 1000]);

        $this->as($admin)->postJson('/admin/cargo/api/fares/value-charge', ['value_charge_pct' => 1.5, 'cargo_type_id' => $this->parcel->id, 'reason' => 'Insurance cover'])
            ->assertOk()->assertJsonPath('applied', true);

        // Each fare gets a new version with the new %; the version it replaces keeps its own
        $this->assertEquals(1.5, (float) CargoFare::where('cargo_type_id', $this->parcel->id)->current()->value('value_charge_pct'));
        $this->assertEquals(0, (float) $otherFare->fresh()->value_charge_pct);

        $this->as($admin)->postJson('/admin/cargo/api/fares/value-charge', ['value_charge_pct' => 150, 'reason' => 'Too much'])
            ->assertStatus(422)->assertJsonValidationErrors('value_charge_pct');
    }
}
