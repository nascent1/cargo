<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\CargoType;
use App\Models\CargoFare;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\Station;

class CargoAdminCrudTest extends TestCase
{
    private function adminUser(): User
    {
        return User::whereHas('roles', fn($q) => $q->where('slug', 'admin'))->firstOrFail();
    }

    // ═══════════════════════════════════════════════════
    // STATIONS
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_stations(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/stations');
        $response->assertOk();
        $response->assertJsonStructure([['id', 'name', 'code', 'city']]);
    }

    public function test_admin_can_create_station(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/stations', [
                'name' => 'Test Station',
                'code' => 'TST-' . rand(100, 999),
                'city' => 'Test City',
                'station_type' => 'port',
                'status' => 'active',
            ]);
        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'Test Station']);
    }

    // ═══════════════════════════════════════════════════
    // ROUTES
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_routes(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/routes-admin');
        $response->assertOk();
    }

    public function test_admin_can_create_route(): void
    {
        $stations = Station::where('status', 'active')->take(2)->get();
        if ($stations->count() < 2) {
            $this->markTestSkipped('Need at least 2 stations');
        }

        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/routes-admin', [
                'name' => 'Test Route',
                'code' => 'TSTRT-' . rand(100, 999),
                'origin_port_id' => $stations[0]->id,
                'destination_port_id' => $stations[1]->id,
                'status' => 'active',
            ]);
        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'Test Route']);
        $response->assertJsonStructure(['origin', 'destination']);
    }

    // ═══════════════════════════════════════════════════
    // TRANSPORT UNITS
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_transport_units(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/transport-units-admin');
        $response->assertOk();
    }

    public function test_admin_can_create_transport_unit(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/transport-units-admin', [
                'name' => 'MV Test Ship',
                'type' => 'Ro-Pax Ferry',
                'transport_type' => 'ferry',
                'registration_number' => 'REG-' . rand(1000, 9999),
                'has_cargo_deck' => true,
                'status' => 'active',
            ]);
        $response->assertCreated();
        $response->assertJsonFragment(['name' => 'MV Test Ship']);
    }

    // ═══════════════════════════════════════════════════
    // TRANSPORT SERVICES
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_services(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/services');
        $response->assertOk();
        $response->assertJsonStructure(['data']);
    }

    public function test_admin_can_create_service(): void
    {
        $route = Route::where('status', 'active')->first();
        $unit = TransportUnit::where('status', 'active')->first();
        if (!$route || !$unit) {
            $this->markTestSkipped('Need active route and transport unit');
        }

        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/services', [
                'route_id' => $route->id,
                'transport_unit_id' => $unit->id,
                'departure_date' => now()->addDays(1)->format('Y-m-d'),
                'departure_time' => '08:00',
                'transport_type' => 'ferry',
                'status' => 'scheduled',
            ]);
        $response->assertCreated();
        $response->assertJsonStructure(['service_number', 'route', 'transport_unit', 'cargo_inventory']);
    }

    // ═══════════════════════════════════════════════════
    // CARGO TYPES (existing CRUD)
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_cargo_types(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/types');
        $response->assertOk();
    }

    public function test_admin_can_create_cargo_type(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->postJson('/admin/cargo/api/types', [
                'name' => 'Test Cargo',
                'code' => 'TSTCG-' . rand(100, 999),
                'category' => 'general',
                'measurement_unit' => 'kg',
                'lane_meters' => 0,
                'max_weight_kg' => 1000,
                'status' => 'active',
            ]);
        $response->assertCreated();
    }

    // ═══════════════════════════════════════════════════
    // CARGO FARES
    // ═══════════════════════════════════════════════════

    public function test_admin_can_list_fares(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->getJson('/admin/cargo/api/fares');
        $response->assertOk();
    }
}
