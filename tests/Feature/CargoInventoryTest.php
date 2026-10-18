<?php

namespace Tests\Feature;

use App\Models\CargoFare;
use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\CargoType;
use App\Models\TransportService;
use App\Models\User;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoFareCalculator;
use App\Services\Cargo\CargoInventoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Space holds, booking totals and cancellation release.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoInventoryTest extends TestCase
{
    use DatabaseTransactions;

    private User $officer;
    private TransportService $service;
    private TransportService $otherService;
    private CargoType $car;
    private CargoType $boxes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->officer = User::whereHas('roles', fn ($q) => $q->whereIn('slug', ['super_admin', 'admin']))->first()
            ?? $this->markTestSkipped('Needs an admin user in the database.');

        $services = TransportService::whereHas('cargoInventory')->limit(2)->get();
        if ($services->count() < 2) {
            $this->markTestSkipped('Needs two transport services with cargo inventory.');
        }
        [$this->service, $this->otherService] = [$services[0], $services[1]];

        // Known, empty capacity on both departures
        foreach ($services as $s) {
            CargoInventory::where('transport_service_id', $s->id)->update([
                'total_weight_capacity_kg' => 10000, 'used_weight_kg' => 0, 'held_weight_kg' => 0,
                'total_volume_capacity_cbm' => 100, 'used_volume_cbm' => 0, 'held_volume_cbm' => 0,
                'total_lane_meters' => 50, 'used_lane_meters' => 0, 'held_lane_meters' => 0,
            ]);
        }

        $this->car = CargoType::create([
            'name' => 'Test car', 'code' => 'TC' . strtoupper(Str::random(8)), 'category' => 'vehicle',
            'measurement_unit' => 'lane_meter', 'lane_meters' => 4.5, 'max_weight_kg' => 2500, 'status' => 'active',
        ]);
        $this->boxes = CargoType::create([
            'name' => 'Test boxes', 'code' => 'TB' . strtoupper(Str::random(8)), 'category' => 'electronics',
            'measurement_unit' => 'cbm', 'lane_meters' => 0, 'max_weight_kg' => 100, 'status' => 'active',
        ]);
        foreach ([$this->service, $this->otherService] as $s) {
            CargoFare::create(['cargo_type_id' => $this->car->id, 'route_id' => $s->route_id, 'price_tzs' => 80000, 'price_usd' => 0, 'status' => 'active']);
            CargoFare::create(['cargo_type_id' => $this->boxes->id, 'route_id' => $s->route_id, 'price_tzs' => 0, 'price_usd' => 0, 'price_per_cbm_tzs' => 50000, 'status' => 'active']);
        }
    }

    private function cart(): array
    {
        return [
            ['cargo_type_id' => $this->car->id, 'weight_kg' => 1500],
            ['cargo_type_id' => $this->boxes->id, 'quantity' => 3, 'weight_kg' => 60, 'length_m' => 1, 'width_m' => 1, 'height_m' => 0.5],
        ];
    }

    private function inv(TransportService $s): CargoInventory
    {
        return CargoInventory::where('transport_service_id', $s->id)->firstOrFail();
    }

    private function inventory(): CargoInventoryService
    {
        return app(CargoInventoryService::class);
    }

    public function test_hold_counts_weight_volume_and_lane_meters(): void
    {
        $lock = $this->inventory()->acquireLock($this->service->id, $this->cart(), $this->officer->id);

        $inv = $this->inv($this->service);
        $this->assertEquals(1560, (float) $inv->held_weight_kg);
        $this->assertEquals(1.5, (float) $inv->held_volume_cbm);
        $this->assertEquals(4.5, (float) $inv->held_lane_meters);
        $this->assertEquals(1.5, (float) $lock->volume_cbm);
    }

    public function test_replacing_a_hold_releases_the_old_one_even_on_another_departure(): void
    {
        $first = $this->inventory()->acquireLock($this->service->id, $this->cart(), $this->officer->id);
        $second = $this->inventory()->acquireLock($this->otherService->id, $this->cart(), $this->officer->id, null, $first->id);

        $this->assertNull(CargoLock::find($first->id));
        $this->assertEquals(0, (float) $this->inv($this->service)->held_weight_kg);
        $this->assertEquals(1560, (float) $this->inv($this->otherService)->held_weight_kg);
        $this->assertNotNull(CargoLock::find($second->id));
    }

    public function test_hold_fails_with_a_clear_message_when_space_is_short(): void
    {
        CargoInventory::where('transport_service_id', $this->service->id)->update(['total_lane_meters' => 3]);

        $this->expectExceptionMessage('lane m needed');
        $this->inventory()->acquireLock($this->service->id, $this->cart(), $this->officer->id);
    }

    public function test_booking_saves_totals_and_cancel_returns_exactly_that_space(): void
    {
        $lock = $this->inventory()->acquireLock($this->service->id, $this->cart(), $this->officer->id);
        $calc = app(CargoFareCalculator::class)->calculateForItems($this->cart(), $this->service->id);

        $consignment = app(CargoBookingService::class)->confirmBooking($lock, [
            'route_id' => $this->service->route_id,
            'sender_name' => 'Sender', 'sender_phone' => '0754000001',
            'receiver_name' => 'Receiver', 'receiver_phone' => '0754000002',
            'currency' => 'TZS', 'payment_method' => 'cash',
            'net_amount' => $calc['net_amount'], 'total_discount' => 0, 'breakdown' => $calc,
        ], $calc['items'], $this->officer->id);

        $this->assertMatchesRegularExpression('/^CG-\d{6}-[A-Z2-9]{5}$/', $consignment->consignment_number);
        $this->assertEquals(1560, (float) $consignment->total_weight_kg);
        $this->assertEquals(1.5, (float) $consignment->total_volume_cbm);
        $this->assertEquals(4, $consignment->total_items);
        $this->assertEquals(4.5, (float) $consignment->items()->sum('lane_meters_consumed'));

        $inv = $this->inv($this->service);
        $this->assertEquals(0, (float) $inv->held_weight_kg);
        $this->assertEquals(1560, (float) $inv->used_weight_kg);
        $this->assertEquals(1.5, (float) $inv->used_volume_cbm);
        $this->assertEquals(4.5, (float) $inv->used_lane_meters);

        app(CargoBookingService::class)->cancelBooking($consignment->fresh(), $this->officer->id, 'Test');

        $inv->refresh();
        $this->assertEquals(0, (float) $inv->used_weight_kg);
        $this->assertEquals(0, (float) $inv->used_volume_cbm);
        $this->assertEquals(0, (float) $inv->used_lane_meters);
    }

    public function test_booking_more_than_was_held_is_refused(): void
    {
        $lock = $this->inventory()->acquireLock($this->service->id, [['cargo_type_id' => $this->boxes->id, 'volume_cbm' => 1, 'weight_kg' => 10]], $this->officer->id);

        $this->expectExceptionMessage('changed after space was held');
        $this->inventory()->commitLock($lock, ['weight_kg' => 10, 'volume_cbm' => 2, 'lane_meters' => 0]);
    }
}
