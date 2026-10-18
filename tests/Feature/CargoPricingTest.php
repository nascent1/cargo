<?php

namespace Tests\Feature;

use App\Exceptions\CargoPricingException;
use App\Models\CargoFare;
use App\Models\CargoType;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Services\Cargo\CargoFareCalculator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CargoFareCalculator — the single pricing engine for the admin terminal and the sales portal.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class CargoPricingTest extends TestCase
{
    use DatabaseTransactions;

    private TransportService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = TransportService::whereNotNull('transport_unit_id')->first()
            ?? $this->markTestSkipped('Needs a transport service.');

        SystemConfig::setValue('cargo_fragile_surcharge_pct', '5');
        SystemConfig::setValue('cargo_hazmat_flat_fee_tzs', '10000');
        SystemConfig::setValue('cargo_livestock_surcharge_pct', '0');
    }

    private function calc(): CargoFareCalculator
    {
        return app(CargoFareCalculator::class);
    }

    private function type(array $attrs = []): CargoType
    {
        return CargoType::create(array_merge([
            'name' => 'Test ' . Str::random(4),
            'code' => 'T' . strtoupper(Str::random(8)),
            'category' => 'general',
            'measurement_unit' => 'kg',
            'lane_meters' => 0,
            'max_weight_kg' => 1000,
            'status' => 'active',
        ], $attrs));
    }

    private function fare(CargoType $type, array $attrs = []): CargoFare
    {
        return CargoFare::create(array_merge([
            'cargo_type_id' => $type->id,
            'route_id' => $this->service->route_id,
            'price_tzs' => 0,
            'price_usd' => 0,
            'status' => 'active',
        ], $attrs));
    }

    private function quote(array $items, float $discount = 0): array
    {
        return $this->calc()->calculateForItems($items, $this->service->id, 'TZS', $discount);
    }

    public function test_per_kg_line_uses_weight_and_minimum_charge(): void
    {
        $type = $this->type();
        $this->fare($type, ['price_per_kg_tzs' => 1500, 'min_charge_tzs' => 20000]);

        $heavy = $this->quote([['cargo_type_id' => $type->id, 'weight_kg' => 40]]);
        $this->assertEquals(60000, $heavy['net_amount']);
        $this->assertSame('per_kg', $heavy['items'][0]['pricing_method']);

        $light = $this->quote([['cargo_type_id' => $type->id, 'weight_kg' => 5]]);
        $this->assertEquals(20000, $light['net_amount']);
        $this->assertSame('minimum', $light['items'][0]['pricing_method']);
    }

    public function test_quantity_multiplies_flat_lines_and_lane_meters(): void
    {
        $goat = $this->type(['measurement_unit' => 'unit', 'category' => 'livestock', 'lane_meters' => 1, 'max_weight_kg' => 100]);
        $this->fare($goat, ['price_tzs' => 15000]);

        $q = $this->quote([['cargo_type_id' => $goat->id, 'quantity' => 4, 'weight_kg' => 120]]);

        $this->assertEquals(60000, $q['net_amount']);
        $this->assertEquals(4, $q['items'][0]['lane_meters_consumed']);
        $this->assertEquals(4, $q['totals']['pieces']);
        $this->assertTrue($q['has_livestock']);
    }

    public function test_dimensions_are_per_piece_and_multiplied_by_quantity(): void
    {
        $type = $this->type(['measurement_unit' => 'cbm']);
        $this->fare($type, ['price_per_cbm_tzs' => 100000]);

        $q = $this->quote([['cargo_type_id' => $type->id, 'quantity' => 2, 'length_m' => 1, 'width_m' => 0.5, 'height_m' => 0.4]]);

        $this->assertEquals(0.4, $q['items'][0]['volume_cbm']);
        $this->assertEquals(40000, $q['net_amount']);
    }

    public function test_vessel_fare_beats_route_fare_and_inactive_fares_are_ignored(): void
    {
        $type = $this->type(['measurement_unit' => 'unit']);
        $this->fare($type, ['price_tzs' => 10000]);
        $vessel = $this->fare($type, ['price_tzs' => 12000, 'transport_unit_id' => $this->service->transport_unit_id]);

        $this->assertEquals(12000, $this->quote([['cargo_type_id' => $type->id]])['net_amount']);

        $vessel->update(['status' => 'inactive']);
        $this->assertEquals(10000, $this->quote([['cargo_type_id' => $type->id]])['net_amount']);
    }

    public function test_fragile_and_hazmat_surcharges_come_from_settings(): void
    {
        $tv = $this->type(['measurement_unit' => 'unit', 'is_fragile' => true]);
        $this->fare($tv, ['price_tzs' => 100000]);
        $chem = $this->type(['requires_hazmat_declaration' => true]);
        $this->fare($chem, ['price_per_kg_tzs' => 1000]);

        $q = $this->quote([
            ['cargo_type_id' => $tv->id],
            ['cargo_type_id' => $chem->id, 'weight_kg' => 10],
        ]);

        $this->assertEquals(5000, $q['items'][0]['surcharge_amount']);
        $this->assertEquals(10000, $q['items'][1]['surcharge_amount']);
        $this->assertEquals(105000 + 20000, $q['net_amount']);

        SystemConfig::setValue('cargo_fragile_surcharge_pct', '10');
        $this->assertEquals(10000, $this->quote([['cargo_type_id' => $tv->id]])['items'][0]['surcharge_amount']);
    }

    public function test_client_discounts_are_ignored_and_server_discount_splits_exactly(): void
    {
        $a = $this->type(['measurement_unit' => 'unit']);
        $this->fare($a, ['price_tzs' => 10000]);
        $b = $this->type(['measurement_unit' => 'unit']);
        $this->fare($b, ['price_tzs' => 20000]);

        $sneaky = $this->quote([['cargo_type_id' => $a->id, 'discount_amount' => 9999]]);
        $this->assertEquals(10000, $sneaky['net_amount']);

        $q = $this->quote([['cargo_type_id' => $a->id], ['cargo_type_id' => $b->id]], 1000);
        $this->assertEquals(29000, $q['net_amount']);
        $this->assertEquals(1000, $q['total_discount']);
        $this->assertEquals(29000, array_sum(array_column($q['items'], 'final_fare')));

        $this->expectException(CargoPricingException::class);
        $this->quote([['cargo_type_id' => $a->id]], 50000);
    }

    public function test_missing_weight_and_overweight_pieces_are_reported_per_line(): void
    {
        $parcel = $this->type(['max_weight_kg' => 30]);
        $this->fare($parcel, ['price_per_kg_tzs' => 1000]);

        try {
            $this->quote([
                ['cargo_type_id' => $parcel->id],
                ['cargo_type_id' => $parcel->id, 'weight_kg' => 45],
            ]);
            $this->fail('Expected a pricing exception.');
        } catch (CargoPricingException $e) {
            $this->assertArrayHasKey('items.0.weight_kg', $e->errors);
            $this->assertArrayHasKey('items.1.weight_kg', $e->errors);
        }

        // 45 kg over 2 pieces is within the per-piece limit
        $this->assertEquals(45000, $this->quote([['cargo_type_id' => $parcel->id, 'weight_kg' => 45, 'quantity' => 2]])['net_amount']);
    }

    public function test_type_without_a_fare_on_the_route_is_reported(): void
    {
        $type = $this->type();

        $this->expectException(CargoPricingException::class);
        $this->quote([['cargo_type_id' => $type->id, 'weight_kg' => 10]]);
    }

    public function test_value_charge_adds_the_fare_percentage_of_declared_value(): void
    {
        $tv = $this->type(['measurement_unit' => 'cbm', 'is_fragile' => true]);
        $this->fare($tv, ['price_per_cbm_tzs' => 45000, 'value_charge_pct' => 1]);

        // 2 × (1.2 × 0.2 × 0.8) = 0.384 m³ → 17,280 base, 5% fragile 864, 1% of 1,000,000 = 10,000
        $q = $this->quote([['cargo_type_id' => $tv->id, 'quantity' => 2, 'length_m' => 1.2, 'width_m' => 0.2, 'height_m' => 0.8, 'declared_value' => 1000000]]);
        $line = $q['items'][0];
        $this->assertEquals(17280, $line['base_fare']);
        $this->assertEquals(864, $line['surcharge_amount']);
        $this->assertEquals(10000, $line['value_charge']);
        $this->assertEquals(1.0, $line['value_charge_pct']);
        $this->assertEquals(28144, $q['net_amount']);
        $this->assertEquals(10000, $q['totals']['value_charge']);

        // A declared value of 0 is allowed and charges nothing
        $this->assertEquals(18144, $this->quote([['cargo_type_id' => $tv->id, 'quantity' => 2, 'length_m' => 1.2, 'width_m' => 0.2, 'height_m' => 0.8, 'declared_value' => 0]])['net_amount']);
    }

    public function test_value_charge_needs_a_declared_value_only_when_the_fare_has_one(): void
    {
        $charged = $this->type();
        $this->fare($charged, ['price_per_kg_tzs' => 1000, 'value_charge_pct' => 2.5]);
        $free = $this->type();
        $this->fare($free, ['price_per_kg_tzs' => 1000]);

        try {
            $this->quote([['cargo_type_id' => $free->id, 'weight_kg' => 10], ['cargo_type_id' => $charged->id, 'weight_kg' => 10]]);
            $this->fail('A missing declared value should be reported.');
        } catch (CargoPricingException $e) {
            $this->assertSame(['items.1.declared_value'], array_keys($e->errors));
            $this->assertStringContainsString('2.5%', $e->errors['items.1.declared_value']);
        }

        // 0% fares keep working without a declared value, and ignore one that is given
        $q = $this->quote([['cargo_type_id' => $free->id, 'weight_kg' => 10, 'declared_value' => 500000]]);
        $this->assertEquals(10000, $q['net_amount']);
        $this->assertEquals(0, $q['items'][0]['value_charge']);
    }

    public function test_minimum_charge_covers_the_base_only_and_discount_spreads_over_the_value_charge(): void
    {
        $type = $this->type();
        $this->fare($type, ['price_per_kg_tzs' => 100, 'min_charge_tzs' => 5000, 'value_charge_pct' => 1]);

        // Base 10 kg × 100 = 1,000 → raised to the 5,000 minimum, then + 1% of 300,000
        $q = $this->quote([['cargo_type_id' => $type->id, 'weight_kg' => 10, 'declared_value' => 300000]], 2000);
        $this->assertSame('minimum', $q['items'][0]['pricing_method']);
        $this->assertEquals(5000, $q['items'][0]['base_fare']);
        $this->assertEquals(3000, $q['items'][0]['value_charge']);
        $this->assertEquals(8000, $q['total_amount']);
        $this->assertEquals(6000, $q['net_amount']);

        $usd = $this->calc()->calculateForItems([['cargo_type_id' => $type->id, 'weight_kg' => 10, 'declared_value' => 1234.56]], $this->service->id, 'USD');
        $this->assertEquals(12.35, $usd['items'][0]['value_charge']);
    }
}
