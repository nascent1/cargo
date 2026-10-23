<?php

namespace Tests\Feature;

use App\Gateways\Payment\SelcomGateway;
use App\Models\Booking;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Mockery;

class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function selcom_webhook_completes_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];

        // Hold a booking
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user);
        
        // Let's set it back to held and pending_payment for this test
        $booking->update([
            'status' => 'held',
            'payment_status' => 'pending'
        ]);
        $booking->items()->update(['status' => 'held']);

        // Mock Selcom Gateway
        $mockGateway = Mockery::mock(SelcomGateway::class)->makePartial();
        $mockGateway->shouldReceive('verifyWebhook')->andReturn(true);
        app()->instance(SelcomGateway::class, $mockGateway);

        // Send webhook payload
        $payload = [
            'order_id' => $booking->booking_reference,
            'payment_status' => 'COMPLETED',
            'transid' => 'TRX123456789'
        ];

        $response = $this->postJson('/api/v1/webhooks/selcom/callback', $payload);

        $response->assertStatus(200)
                 ->assertJsonPath('status', 'success')
                 ->assertJsonPath('message', 'Payment processed');

        $booking->refresh();
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals('completed', $booking->payment_status);
        
        $ticket = $booking->items->first();
        $this->assertEquals('confirmed', $ticket->status);
    }
}
