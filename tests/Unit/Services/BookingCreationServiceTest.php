<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\Ticket;
use App\Models\Passenger;
use App\Models\SystemConfig;
use App\Models\User;
use App\Services\BookingCreationService;
use App\Services\DiscountService;
use App\Services\CouponService;
use App\Services\PaymentProcessingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingCreationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingCreationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->service = app(BookingCreationService::class);
    }

    /** @test */
    public function it_creates_a_hold_successfully_with_multiple_passengers(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'ferry_class_id' => $scenario['class']->id,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'base_fare' => 35000,
                    'gender' => 'male',
                ],
                [
                    'ferry_class_id' => $scenario['class']->id,
                    'first_name' => 'Jane',
                    'last_name' => 'Doe',
                    'base_fare' => 35000,
                    'gender' => 'female',
                ]
            ]
        ];

        $booking = $this->service->createHold($payload, $scenario['user']);

        $this->assertInstanceOf(Booking::class, $booking);
        $this->assertEquals('held', $booking->status);
        $this->assertEquals(70000, $booking->total_amount);
        $this->assertEquals(70000, $booking->net_amount);
        $this->assertCount(2, $booking->items);
        $this->assertTrue(str_starts_with($booking->booking_reference, 'VFF-'));
    }

    /** @test */
    public function it_rejects_creation_if_voyage_is_not_accepting_reservations(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $scenario['voyage']->update(['status' => 'departed']);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                ['ferry_class_id' => $scenario['class']->id]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('no longer accepting new reservations');

        $this->service->createHold($payload, $scenario['user']);
    }

    /** @test */
    public function it_rejects_creation_if_agent_is_not_authorized_for_vessel(): void
    {
        $agentData = $this->createAgentWithAccount();
        $scenario = $this->createCompleteBookingScenario();
        
        // Remove agent's permission to book on this ferry
        $agentData['agent']->ferries()->detach();

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                ['ferry_class_id' => $scenario['class']->id]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('not authorized to book tickets on vessel');

        $this->service->createHold($payload, $agentData['user']);
    }

    /** @test */
    public function it_requires_at_least_one_passenger(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => []
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('At least one passenger must be specified');

        $this->service->createHold($payload, $scenario['user']);
    }

    /** @test */
    public function it_correctly_uses_agent_ticket_prefix(): void
    {
        SystemConfig::setValue('ticket_prefix_agent', 'VAF');
        $agentData = $this->createAgentWithAccount();
        $scenario = $this->createCompleteBookingScenario();
        $agentData['agent']->ferries()->attach($scenario['voyage']->ferry_id);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 35000,
                ]
            ]
        ];

        $booking = $this->service->createHold($payload, $agentData['user']);

        $this->assertTrue(str_starts_with($booking->booking_reference, 'VAF-'));
    }

    /** @test */
    public function it_validates_seat_availability_and_rejects_double_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        // Create an existing held booking with seat 14A
        $booking1 = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user']);
        $booking1->items()->first()->update(['seat_number' => '14A', 'status' => 'confirmed']);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'ferry_class_id' => $scenario['class']->id,
                    'seat_number' => '14A',
                    'base_fare' => 35000,
                ]
            ]
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('has already been reserved');

        $this->service->createHold($payload, $scenario['user']);
    }

    /** @test */
    public function it_applies_automatic_discounts(): void
    {
        // Mock DiscountService
        $discountServiceMock = \Mockery::mock(DiscountService::class);
        $discountServiceMock->shouldReceive('applyToBooking')->once()->andReturn(5000.00);
        
        $this->app->instance(DiscountService::class, $discountServiceMock);
        
        $service = app(BookingCreationService::class);
        $scenario = $this->createCompleteBookingScenario();

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 35000,
                ]
            ]
        ];

        $booking = $service->createHold($payload, $scenario['user']);

        $this->assertEquals(5000, $booking->discount_amount);
        $this->assertEquals(30000, $booking->net_amount);
    }

    /** @test */
    public function it_successfully_completes_a_pending_booking(): void
    {
        $paymentMock = \Mockery::mock(PaymentProcessingService::class);
        $paymentMock->shouldReceive('processStandardPayment')->once()->andReturn(true);
        $paymentMock->shouldReceive('processAgentWalletDeduction')->once()->andReturn(true);
        $this->app->instance(PaymentProcessingService::class, $paymentMock);
        
        $service = app(BookingCreationService::class);

        $scenario = $this->createCompleteBookingScenario();
        
        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                ['ferry_class_id' => $scenario['class']->id, 'base_fare' => 35000]
            ]
        ];
        
        $booking = $service->createHold($payload, $scenario['user']);
        
        $paymentData = ['payment_channel' => 'cash', 'transaction_reference' => 'TX-CASH-123'];
        
        $completedBooking = $service->completeBooking($booking, $paymentData, $scenario['user']);
        
        $this->assertEquals('confirmed', $completedBooking->status);
        $this->assertEquals('confirmed', $completedBooking->items->first()->status);
        $this->assertEquals('cash', $completedBooking->payment_channel);
    }
}
