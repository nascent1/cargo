<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Ferry;
use App\Models\FerryClass;
use App\Models\Passenger;
use App\Models\Route;
use App\Models\SystemConfig;
use App\Models\Ticket;
use App\Models\TravelClass;
use App\Models\Voyage;
use App\Services\CancellationPenaltyCalculator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\CreatesTestData;
use Tests\TestCase;

class CancellationPenaltyCalculatorTest extends TestCase
{
    use RefreshDatabase, CreatesTestData;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ── Flat Mode Tests ──────────────────────────────────────

    #[Test]
    public function flat_mode_returns_configured_percentage(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '10');

        $result = CancellationPenaltyCalculator::calculate(100000.00);

        $this->assertEquals(10.0, $result['percent']);
        $this->assertEquals(10000.00, $result['amount']);
        $this->assertEquals(90000.00, $result['refund']);
    }

    #[Test]
    public function flat_mode_uses_custom_percentage(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '25');

        $result = CancellationPenaltyCalculator::calculate(200000.00);

        $this->assertEquals(25.0, $result['percent']);
        $this->assertEquals(50000.00, $result['amount']);
        $this->assertEquals(150000.00, $result['refund']);
    }

    #[Test]
    public function flat_mode_ignores_voyage_timing(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '15');

        // Create a ticket with a voyage departing in 1 hour (would be 100% in tiered)
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 1);

        $result = CancellationPenaltyCalculator::calculate(
            (float) $data['ticket']->final_fare,
            $data['ticket']
        );

        // Flat mode: should still be 15%, NOT 100%
        $this->assertEquals(15.0, $result['percent']);
    }

    // ── Disabled Mode Tests ──────────────────────────────────

    #[Test]
    public function disabled_penalty_returns_zero(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '0');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '10');

        $result = CancellationPenaltyCalculator::calculate(100000.00);

        $this->assertEquals(0.0, $result['percent']);
        $this->assertEquals(0.0, $result['amount']);
        $this->assertEquals(100000.00, $result['refund']);
    }

    #[Test]
    public function disabled_penalty_returns_zero_even_for_tiered(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '0');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');

        $data = $this->createVoyageAndTicket(departureHoursFromNow: 1);
        $result = CancellationPenaltyCalculator::calculate(50000.00, $data['ticket']);

        $this->assertEquals(0.0, $result['percent']);
        $this->assertEquals(0.0, $result['amount']);
        $this->assertEquals(50000.00, $result['refund']);
    }

    // ── Tiered Mode Tests ────────────────────────────────────

    #[Test]
    public function tiered_mode_free_cancellation_beyond_partial_hours(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '2');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '24');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '25');

        // Voyage departs in 48 hours — well beyond the 24h partial window
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 48);

        $result = CancellationPenaltyCalculator::calculate(
            (float) $data['ticket']->final_fare,
            $data['ticket']
        );

        $this->assertEquals(0.0, $result['percent']);
        $this->assertEquals(0.0, $result['amount']);
        $this->assertEquals((float) $data['ticket']->final_fare, $result['refund']);
    }

    #[Test]
    public function tiered_mode_partial_penalty_within_partial_window(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '2');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '24');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '25');

        // Voyage departs in 12 hours — within 24h but beyond 2h
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 12);

        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);

        $this->assertEquals(25.0, $result['percent']);
        $this->assertEquals(25000.00, $result['amount']);
        $this->assertEquals(75000.00, $result['refund']);
    }

    #[Test]
    public function tiered_mode_full_penalty_within_full_window(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '2');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '24');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '25');

        // Voyage departs in 1 hour — within the 2h full penalty window
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 1);

        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);

        $this->assertEquals(100.0, $result['percent']);
        $this->assertEquals(100000.00, $result['amount']);
        $this->assertEquals(0.0, $result['refund']);
    }

    #[Test]
    public function tiered_mode_full_penalty_after_departure(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');

        // Voyage departed 2 hours ago
        $data = $this->createVoyageAndTicket(departureHoursFromNow: -2);

        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);

        $this->assertEquals(100.0, $result['percent']);
        $this->assertEquals(100000.00, $result['amount']);
        $this->assertEquals(0.0, $result['refund']);
    }

    #[Test]
    public function tiered_mode_at_exact_boundary_of_partial_window(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '2');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '24');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '25');

        // Exactly 24 hours before departure — boundary case
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 24);

        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);

        // ≤ 24h means partial penalty applies
        $this->assertEquals(25.0, $result['percent']);
    }

    #[Test]
    public function tiered_mode_at_exact_boundary_of_full_window(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '2');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '24');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '25');

        // Exactly 2 hours before departure — boundary case
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 2);

        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);

        // ≤ 2h means full penalty applies
        $this->assertEquals(100.0, $result['percent']);
    }

    // ── Fallback Tests ───────────────────────────────────────

    #[Test]
    public function tiered_mode_falls_back_to_flat_when_no_voyage(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_penalty_percentage', '10');

        // No ticket/voyage context — should fall back to flat
        $result = CancellationPenaltyCalculator::calculate(100000.00);

        $this->assertEquals(10.0, $result['percent']);
        $this->assertEquals(10000.00, $result['amount']);
    }

    #[Test]
    public function get_penalty_percent_returns_correct_value(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '15');

        $percent = CancellationPenaltyCalculator::getPenaltyPercent();

        $this->assertEquals(15.0, $percent);
    }

    // ── Rounding Tests ───────────────────────────────────────

    #[Test]
    public function penalty_amount_is_rounded_to_two_decimals(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '7');

        $result = CancellationPenaltyCalculator::calculate(33333.33);

        // 33333.33 × 0.07 = 2333.3331 → should round to 2333.33
        $this->assertEquals(2333.33, $result['amount']);
        $this->assertEquals(31000.00, $result['refund']);
    }

    #[Test]
    public function refund_is_never_negative(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'flat');
        SystemConfig::setValue('cancellation_penalty_percentage', '100');

        $result = CancellationPenaltyCalculator::calculate(50000.00);

        $this->assertEquals(50000.00, $result['amount']);
        $this->assertEquals(0.0, $result['refund']);
    }

    // ── Tiered Mode with Custom Config ───────────────────────

    #[Test]
    public function tiered_mode_respects_custom_thresholds(): void
    {
        SystemConfig::setValue('cancellation_penalty_enabled', '1');
        SystemConfig::setValue('cancellation_penalty_mode', 'tiered');
        SystemConfig::setValue('cancellation_full_penalty_hours', '6');
        SystemConfig::setValue('cancellation_partial_penalty_hours', '48');
        SystemConfig::setValue('cancellation_partial_penalty_rate', '15');

        // 30 hours before departure — within 48h but beyond 6h → 15%
        $data = $this->createVoyageAndTicket(departureHoursFromNow: 30);
        $result = CancellationPenaltyCalculator::calculate(100000.00, $data['ticket']);
        $this->assertEquals(15.0, $result['percent']);

        // 5 hours before departure — within 6h → 100%
        $data2 = $this->createVoyageAndTicket(departureHoursFromNow: 5);
        $result2 = CancellationPenaltyCalculator::calculate(100000.00, $data2['ticket']);
        $this->assertEquals(100.0, $result2['percent']);

        // 60 hours before departure — beyond 48h → 0%
        $data3 = $this->createVoyageAndTicket(departureHoursFromNow: 60);
        $result3 = CancellationPenaltyCalculator::calculate(100000.00, $data3['ticket']);
        $this->assertEquals(0.0, $result3['percent']);
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Create a voyage + ticket with a departure time N hours from now.
     */
    protected function createVoyageAndTicket(int $departureHoursFromNow = 48): array
    {
        $routeAndFerry = $this->createRouteAndFerry();
        $departureTime = Carbon::now()->addHours($departureHoursFromNow);

        $trip = \App\Models\Trip::firstOrCreate([
            'route_id' => $routeAndFerry['route']->id,
            'departure_time' => '08:00:00',
            'arrival_time' => '10:00:00',
            'status' => 'active'
        ]);

        $schedule = \App\Models\Schedule::firstOrCreate([
            'trip_id' => $trip->id,
            'ferry_id' => $routeAndFerry['ferry']->id,
            'start_date' => Carbon::now()->subDays(10)->toDateString(),
            'end_date' => Carbon::now()->addDays(30)->toDateString(),
            'status' => 'active',
            'frequency_pattern' => 'daily'
        ]);

        $voyage = Voyage::create([
            'ferry_id' => $routeAndFerry['ferry']->id,
            'route_id' => $routeAndFerry['route']->id,
            'schedule_id' => $schedule->id,
            'departure_date' => $departureTime->toDateString(),
            'departure_time' => $departureTime->toTimeString(),
            'arrival_time' => $departureTime->addHours(2)->toTimeString(),
            'status' => 'scheduled',
        ]);

        $passenger = Passenger::create([
            'first_name' => 'Test',
            'last_name' => 'Passenger',
            'gender' => 'male',
        ]);

        $booking = Booking::create([
            'booking_reference' => 'BK-TEST-' . strtoupper(uniqid()),
            'status' => 'confirmed',
            'total_amount' => 100000,
            'net_amount' => 100000,
            'currency' => 'TZS',
            'payment_status' => 'paid',
        ]);

        $ticket = Ticket::create([
            'booking_id' => $booking->id,
            'ticket_number' => 'VFF-TEST-' . strtoupper(uniqid()),
            'voyage_id' => $voyage->id,
            'ferry_class_id' => $routeAndFerry['ferryClass']->id,
            'passenger_id' => $passenger->id,
            'base_fare' => 100000,
            'final_fare' => 100000,
            'status' => 'confirmed',
            'currency' => 'TZS',
            'qr_code_token' => \Illuminate\Support\Str::random(32),
        ]);

        return compact('voyage', 'booking', 'ticket', 'passenger');
    }

    protected function createRouteAndFerry(): array
    {
        $portA = \App\Models\Port::firstOrCreate(['name' => 'Port A'], ['code' => 'PA', 'status' => 'active']);
        $portB = \App\Models\Port::firstOrCreate(['name' => 'Port B'], ['code' => 'PB', 'status' => 'active']);
        $route = Route::firstOrCreate(
            ['name' => 'Test Route'],
            ['origin_port_id' => $portA->id, 'destination_port_id' => $portB->id, 'status' => 'active']
        );

        $ferry = Ferry::firstOrCreate(['name' => 'Test Ferry'], ['capacity' => 500, 'status' => 'active']);

        $travelClass = TravelClass::firstOrCreate(['name' => 'Economy'], ['description' => 'Economy Class', 'sort_order' => 1]);
        $ferryClass = FerryClass::firstOrCreate(
            ['ferry_id' => $ferry->id, 'class_id' => $travelClass->id],
            ['no_seats' => 200, 'sort_order' => 1]
        );

        return compact('route', 'ferry', 'ferryClass', 'travelClass');
    }
}
