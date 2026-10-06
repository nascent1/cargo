<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function customer_can_hold_and_pay_for_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        // 1. Hold seats
        $holdPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'first_name' => 'Alice',
                    'last_name' => 'Smith',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 50000,
                    'seat_number' => 'A1'
                ],
                [
                    'first_name' => 'Bob',
                    'last_name' => 'Smith',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 50000,
                    'seat_number' => 'A2'
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/bookings/hold', $holdPayload);
        
        $response->assertStatus(201)
                 ->assertJsonPath('success', true);
                 
        $bookingRef = $response->json('data.booking_reference');
        $this->assertNotNull($bookingRef);

        // Verify booking in DB
        $booking = Booking::where('booking_reference', $bookingRef)->first();
        $this->assertEquals('held', $booking->status);
        $this->assertEquals(100000, $booking->total_amount);

        // 2. Fetch booking summary
        $summaryResponse = $this->getJson("/api/v1/bookings/{$bookingRef}");
        $summaryResponse->assertStatus(200)
                        ->assertJsonPath('data.booking_reference', $bookingRef)
                        ->assertJsonPath('data.status', 'held');

        // 3. Pay for booking
        $payPayload = [
            'payment_method' => 'SELCOM_MOMO',
            'amount' => 100000,
            'currency' => 'TZS',
            'phone_number' => '255700000000'
        ];

        // This will simulate the payment. For tests, we might need to mock the gateway, 
        // but the BookingController might just use the PaymentProcessingService which we can test via standard endpoints if it's integrated,
        // Wait, looking at BookingController, there's `pay` endpoint.
        
        config([
            'services.selcom.api_key' => 'test-key',
            'services.selcom.api_secret' => 'test-secret',
            'services.selcom.vendor' => 'test-vendor'
        ]);

        \Illuminate\Support\Facades\Http::fake([
            '*' => \Illuminate\Support\Facades\Http::response([
                'result' => 'SUCCESS',
                'resultcode' => '000',
                'message' => 'Payment initiated',
                'data' => [
                    [
                        'transid' => 'SELCOM123',
                        'reference' => $bookingRef
                    ]
                ]
            ], 200)
        ]);

        $payResponse = $this->actingAs($user)->postJson("/api/v1/bookings/{$bookingRef}/pay", $payPayload);
        
        $payResponse->assertStatus(200)
                    ->assertJsonPath('success', true)
                    ->assertJsonPath('message', 'A payment prompt has been sent to your phone. Please confirm.');
    }
}
