<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Services\BookingCreationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InventoryConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function simultaneous_bookings_for_same_seat_prevent_double_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user1 = $this->createPassengerUser();
        $user2 = $this->createPassengerUser();

        $bookingService = app(BookingCreationService::class);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'currency' => 'TZS',
            'passengers' => [
                [
                    'first_name' => 'Alice',
                    'last_name' => 'Smith',
                    'ferry_class_id' => $scenario['class']->id,
                    'base_fare' => 50000,
                    'seat_number' => 'A1'
                ]
            ]
        ];

        // Simulate Process 1
        $process1 = function () use ($bookingService, $payload, $user1) {
            DB::transaction(function () use ($bookingService, $payload, $user1) {
                $bookingService->createHold($payload, $user1);
            });
        };

        // Simulate Process 2
        $process2 = function () use ($bookingService, $payload, $user2) {
            DB::transaction(function () use ($bookingService, $payload, $user2) {
                $bookingService->createHold($payload, $user2);
            });
        };

        // Execute Process 1 (should succeed)
        $process1();

        // Execute Process 2 (should fail because seat A1 is now taken)
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Seat A1 has already been reserved');
        
        $process2();
    }
}
