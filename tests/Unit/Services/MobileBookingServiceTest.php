<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Voyage;
use App\Models\FerryClass;
use App\Services\MobileBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MobileBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MobileBookingService::class);
    }

    /** @test */
    public function it_generates_cryptographic_qr_token_not_predictable(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        $booking = $this->service->createReservation([
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'name' => 'John Doe',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'male',
                ],
            ],
        ], $scenario['user']->id);

        $ticket = $booking->items->first();

        // BUG-007: QR token must NOT be predictable (no booking ref or counter)
        $this->assertNotNull($ticket->qr_code_token);
        $this->assertStringNotContainsString($booking->booking_reference, $ticket->qr_code_token);
        $this->assertStringNotContainsString('ITEM-', $ticket->qr_code_token);

        // Must be hex-encoded random bytes (32 hex chars = 16 bytes)
        $this->assertEquals(32, strlen($ticket->qr_code_token));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $ticket->qr_code_token);
    }

    /** @test */
    public function it_generates_unique_qr_tokens_per_ticket(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        $booking = $this->service->createReservation([
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'name' => 'Alice One',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'female',
                ],
                [
                    'name' => 'Bob Two',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'male',
                ],
            ],
        ], $scenario['user']->id);

        $tokens = $booking->items->pluck('qr_code_token')->toArray();
        $this->assertCount(2, $tokens);
        $this->assertCount(2, array_unique($tokens), 'Each ticket must have a unique QR token');
    }

    /** @test */
    public function it_uses_usd_pricing_for_usd_currency_bookings(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        // Set up USD fare on the fare record
        $fare = \App\Models\Fare::where('route_id', $scenario['voyage']->route_id)
            ->where('ferry_class_id', $scenario['class']->id)
            ->first();
        $fare->update(['adult_price_usd' => 25.00]);

        $booking = $this->service->createReservation([
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'USD',
            'passengers' => [
                [
                    'name' => 'Tourist Visitor',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'male',
                ],
            ],
        ], $scenario['user']->id);

        $ticket = $booking->items->first();

        // BUG-008: USD booking should use USD fare, not TZS
        $this->assertEquals(25.00, (float) $ticket->base_fare);
        $this->assertEquals(25.00, (float) $ticket->final_fare);
        $this->assertEquals('USD', $booking->currency);
    }

    /** @test */
    public function it_uses_tzs_pricing_for_tzs_currency_bookings(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        $booking = $this->service->createReservation([
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'name' => 'Local Resident',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'female',
                ],
            ],
        ], $scenario['user']->id);

        $ticket = $booking->items->first();

        // TZS booking should use TZS fare (35000 from createCompleteBookingScenario)
        $this->assertEquals(35000.00, (float) $ticket->base_fare);
        $this->assertEquals(35000.00, (float) $ticket->final_fare);
    }

    /** @test */
    public function it_returns_seat_layout_from_ferry_config_not_hardcoded(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $ferry = $scenario['voyage']->ferry;

        // Configure a real layout on the ferry
        $ferry->update([
            'has_layout' => true,
            'seat_layout_json' => [
                (string) $scenario['class']->id => [
                    'grid' => [
                        ["1A", "1B", null, "1C", "1D"],
                        ["2A", "2B", null, "2C", "2D"],
                    ]
                ]
            ]
        ]);

        $result = $this->service->getSeatLayout($scenario['voyage']->id, $scenario['class']->id);

        // BUG-015: Should return the actual layout, not hardcoded 3x5
        $this->assertEquals(2, $result['layout']['rows']);
        $this->assertEquals(5, $result['layout']['columns']);
        $this->assertEquals("1A", $result['layout']['grid'][0][0]);
        $this->assertEquals("2D", $result['layout']['grid'][1][4]);
    }

    /** @test */
    public function it_falls_back_to_capacity_grid_when_no_layout_configured(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $ferry = $scenario['voyage']->ferry;
        $ferry->update(['has_layout' => false, 'seat_layout_json' => null]);

        $result = $this->service->getSeatLayout($scenario['voyage']->id, $scenario['class']->id);

        // Should generate a dynamic grid based on class capacity, not a hardcoded 3x5
        $this->assertNotEmpty($result['layout']['grid']);
        $this->assertIsArray($result['layout']['sold']);
        $this->assertIsArray($result['layout']['held']);
    }

    /** @test */
    public function it_rejects_reservation_too_close_to_departure(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        // Set departure to 30 minutes from now (within the 2-hour expiry window)
        $scenario['voyage']->update([
            'departure_date' => now()->toDateString(),
            'departure_time' => now()->addMinutes(30)->toTimeString(),
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('too late to reserve');

        $this->service->createReservation([
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'name' => 'Late Booker',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'gender' => 'male',
                ],
            ],
        ], $scenario['user']->id);
    }
}
