<?php

namespace Tests\Unit\Services;

use App\Models\Booking;
use App\Models\BookingLock;
use App\Models\Ticket;
use App\Models\Passenger;
use App\Services\BookingLockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BookingLockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BookingLockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(BookingLockService::class);
    }

    #[Test]
    public function it_acquires_a_lock_for_available_seats(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $this->createSystemUser();

        $lock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $scenario['class']->id,
            quantity: 2,
            userId: $user->id
        );

        $this->assertInstanceOf(BookingLock::class, $lock);
        $this->assertEquals(2, $lock->quantity);
        $this->assertEquals($scenario['voyage']->id, $lock->voyage_id);
        $this->assertFalse($lock->expires_at->isPast());
    }

    #[Test]
    public function it_prevents_overbooking_beyond_capacity(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $class = $scenario['class'];
        $class->update(['no_seats' => 5]); // Set capacity to 5

        $user = $this->createSystemUser();

        // Lock all 5 seats
        $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 5,
            userId: $user->id
        );

        // Attempt to lock 1 more should fail
        $this->expectException(\Exception::class);
        $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 1,
            userId: $this->createSystemUser()->id
        );
    }

    #[Test]
    public function it_releases_locks_correctly(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $this->createSystemUser();

        $lock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $scenario['class']->id,
            quantity: 3,
            userId: $user->id
        );

        $this->service->releaseLock($lock->id);

        $this->assertNull(BookingLock::find($lock->id));
    }

    #[Test]
    public function it_consumes_lock_into_booking(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $this->createSystemUser();

        $lock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $scenario['class']->id,
            quantity: 1,
            userId: $user->id
        );

        $lockId = $lock->id;

        // Create a booking from the lock
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $user, 1);

        // Release the lock (simulating consumption)
        $this->service->releaseLock($lockId);

        $this->assertNull(BookingLock::find($lockId));
        $this->assertDatabaseHas('bookings', ['id' => $booking->id, 'status' => 'confirmed']);
    }

    #[Test]
    public function it_detects_expired_locks(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $user = $this->createSystemUser();

        $lock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $scenario['class']->id,
            quantity: 1,
            userId: $user->id
        );

        // Manually expire the lock
        $lock->update(['expires_at' => now()->subMinutes(10)]);

        // The expired lock should not block new acquisitions
        $newLock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $scenario['class']->id,
            quantity: 1,
            userId: $this->createSystemUser()->id
        );

        $this->assertInstanceOf(BookingLock::class, $newLock);
    }

    #[Test]
    public function concurrent_locks_respect_total_capacity(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $class = $scenario['class'];
        $class->update(['no_seats' => 10]);

        // Lock 4 seats for user A
        $userA = $this->createSystemUser();
        $lockA = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 4,
            userId: $userA->id
        );

        // Lock 4 seats for user B
        $userB = $this->createSystemUser();
        $lockB = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 4,
            userId: $userB->id
        );

        // User C tries to lock 4 → only 2 available → should fail
        $userC = $this->createSystemUser();
        $this->expectException(\Exception::class);
        $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 4,
            userId: $userC->id
        );
    }

    #[Test]
    public function sold_tickets_reduce_available_capacity(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $class = $scenario['class'];
        $class->update(['no_seats' => 5]);

        // Create 3 confirmed tickets
        $user = $this->createSystemUser();
        $this->createConfirmedBooking($scenario['voyage'], $class, $user, 3);

        // Should only be able to lock 2 more
        $newUser = $this->createSystemUser();
        $lock = $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 2,
            userId: $newUser->id
        );
        $this->assertNotNull($lock);

        // Trying for 1 more should fail
        $this->expectException(\Exception::class);
        $this->service->acquireLock(
            voyageId: $scenario['voyage']->id,
            classId: $class->id,
            quantity: 1,
            userId: $this->createSystemUser()->id
        );
    }
}
