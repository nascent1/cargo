<?php

namespace Tests\Feature;

use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancellationFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function customer_can_cancel_held_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        // 1. Hold seats
        $holdPayload = [
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'first_name' => 'Cancel',
                    'last_name' => 'Me',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 50000,
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/v1/bookings/hold', $holdPayload);
        $response->assertStatus(201);
                 
        $bookingRef = $response->json('data.booking_reference');

        // 2. Cancel the hold
        $cancelPayload = [
            'reason' => 'Changed my mind'
        ];

        $cancelResponse = $this->actingAs($user)->postJson("/api/v1/bookings/{$bookingRef}/cancel", $cancelPayload);
        
        $cancelResponse->assertStatus(200)
                       ->assertJsonPath('success', true)
                       ->assertJsonPath('message', 'Booking cancelled successfully.');
                       
        $booking = Booking::where('booking_reference', $bookingRef)->first();
        $this->assertEquals('cancelled', $booking->status);
    }
}
