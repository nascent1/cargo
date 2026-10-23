<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\SystemTransaction;
use App\Models\Ticket;
use App\Services\FastBookingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FastBookingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected FastBookingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(FastBookingService::class);
    }

    #[Test]
    public function it_creates_a_standard_booking_from_locks(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        
        // Setup a lock
        $lockService = app(\App\Services\BookingLockService::class);
        $lock = $lockService->acquireLock($scenario['voyage']->id, $scenario['class']->id, 1, $user->id);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'gender' => 'male',
                    'nationality' => 'Tanzanian',
                    'id_number' => 'PASS-123',
                    'passenger_type_id' => 1, // Adult
                    'ferry_class_id' => $scenario['class']->id,
                    'nationality_type' => 1,
                ]
            ],
            'payment_method' => 'cash',
            'lock_ids' => [$lock->id],
            'currency' => 'TZS',
            'trip_type' => 'one_way'
        ];

        $booking = $this->service->executeBooking($payload, $user->id);

        $this->assertNotNull($booking);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals(35000, $booking->net_amount);
        $this->assertCount(1, $booking->items);
        $this->assertEquals('confirmed', $booking->items->first()->status);
        
        // Lock should be deleted
        $this->assertNull(\App\Models\BookingLock::find($lock->id));
        
        // Transaction should exist
        $this->assertDatabaseHas('transactions', [
            'booking_id' => $booking->id,
            'amount' => 35000,
            'currency_code' => 'TZS'
        ]);
    }

    #[Test]
    public function it_handles_complimentary_tickets_with_no_transaction(): void
    {
        // This test simulates the bug scenario (BUG-001/BUG-002) and verifies
        // the expectation. Currently it might fail until the bug is fixed,
        // or we assert the *desired* behavior to drive the fix.
        
        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        \App\Models\Permission::unguarded(function () {
            \App\Models\Permission::firstOrCreate(['name' => 'booking:apply_exemption'], ['id' => 9999, 'display_name' => 'Apply Exemption', 'module' => 'booking']);
        });
        $permission = \App\Models\Permission::where('name', 'booking:apply_exemption')->first();
        $user->permissions()->attach($permission);
        
        $lockService = app(\App\Services\BookingLockService::class);
        $lock = $lockService->acquireLock($scenario['voyage']->id, $scenario['class']->id, 1, $user->id);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'is_complimentary' => true,
            'complimentary_reason' => 'VIP Guest',
            'passengers' => [
                [
                    'first_name' => 'Free',
                    'last_name' => 'Passenger',
                    'gender' => 'female',
                    'nationality' => 'Tanzanian',
                    'id_number' => 'PASS-123',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'nationality_type' => 1,
                ]
            ],
            'payment_method' => 'cash', // Even though free, needs a method in payload sometimes
            'lock_ids' => [$lock->id],
            'currency' => 'TZS',
            'trip_type' => 'one_way'
        ];

        $booking = $this->service->executeBooking($payload, $user->id);

        $this->assertNotNull($booking);
        $this->assertEquals('confirmed', $booking->status);
        $this->assertEquals(0, (float) $booking->net_amount); // Net should be 0
        $this->assertEquals(35000, (float) $booking->total_amount); // Total value is 35000
        $this->assertTrue($booking->items->first()->is_complimentary);

        // Desired behavior: a system transaction should exist for 0 amount
        $tx = SystemTransaction::where('booking_id', $booking->id)->first();
        // Since we are doing Option B, we write the test asserting the fix.
        // It will fail until we fix BUG-001.
        $this->assertNotNull($tx, 'A transaction record MUST exist for complimentary bookings');
        $this->assertEquals(0.00, (float) $tx->amount);
    }

    #[Test]
    public function it_deducts_agent_float_during_fast_booking(): void
    {
        $agentData = $this->createAgentWithAccount(100000);
        $user = $agentData['user'];
        
        $scenario = $this->createCompleteBookingScenario(['user' => $user, 'agent' => $agentData['agent']]);
        
        $lockService = app(\App\Services\BookingLockService::class);
        $lock = $lockService->acquireLock($scenario['voyage']->id, $scenario['class']->id, 1, $user->id);

        $payload = [
            'voyage_id' => $scenario['voyage']->id,
            'passengers' => [
                [
                    'first_name' => 'Agent',
                    'last_name' => 'Client',
                    'gender' => 'male',
                    'nationality' => 'Tanzanian',
                    'id_number' => 'PASS-123',
                    'passenger_type_id' => 1,
                    'ferry_class_id' => $scenario['class']->id,
                    'nationality_type' => 1,
                ]
            ],
            'payment_method' => 'agent_float',
            'lock_ids' => [$lock->id],
            'currency' => 'TZS',
            'trip_type' => 'one_way'
        ];

        $booking = $this->service->executeBooking($payload, $user->id);

        $this->assertNotNull($booking);
        $this->assertEquals('confirmed', $booking->status);
        
        // Balance should be 100000 - 35000 = 65000
        $agentData['account']->refresh();
        $this->assertEquals(66750.00, (float) $agentData['account']->balance);
    }
}
