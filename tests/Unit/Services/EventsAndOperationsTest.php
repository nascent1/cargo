<?php

namespace Tests\Unit\Services;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingExpired;
use App\Events\TicketRescheduled;
use App\Models\Booking;
use App\Models\SystemConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Tests for event dispatch, reconciliation command, and booking expiry.
 * Covers tasks 5.3, 5.5, 5.10 from the implementation plan.
 */
class EventsAndOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedReferenceData();
    }

    // ── Event Dispatch Tests ─────────────────────────────────

    /** @test */
    public function booking_cancelled_event_is_dispatched(): void
    {
        Event::fake([BookingCancelled::class]);

        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1);

        $service = app(\App\Services\BookingCancellationService::class);
        $service->cancelBooking($booking, 'Testing event', $scenario['user']);

        Event::assertDispatched(BookingCancelled::class, function ($event) use ($booking) {
            return $event->booking->id === $booking->id
                && $event->reason === 'Testing event';
        });
    }

    /** @test */
    public function booking_cancelled_event_carries_penalty_data(): void
    {
        Event::fake([BookingCancelled::class]);

        SystemConfig::updateOrCreate(['key_name' => 'cancellation_penalty_percentage'], ['value' => '20', 'group' => 'booking']);

        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1);

        $service = app(\App\Services\BookingCancellationService::class);
        $service->cancelBooking($booking, 'Penalty test', $scenario['user']);

        Event::assertDispatched(BookingCancelled::class, function ($event) {
            return $event->penaltyAmount > 0;
        });
    }

    /** @test */
    public function booking_expired_event_is_dispatched_on_expiry(): void
    {
        Event::fake([BookingExpired::class]);

        $scenario = $this->createCompleteBookingScenario();
        $user = $scenario['user'];
        $booking = Booking::create([
            'booking_reference' => 'EXP-' . uniqid(),
            'status' => 'held',
            'payment_status' => 'unpaid',
            'net_amount' => 35000,
            'currency' => 'TZS',
            'booked_by_user_id' => $user->id,
            'expires_at' => now()->subMinutes(10),
        ]);

        $this->artisan('bookings:expire-holds')
            ->assertExitCode(0);

        Event::assertDispatched(BookingExpired::class, function ($event) use ($booking) {
            return $event->booking->id === $booking->id;
        });
    }

    /** @test */
    public function booking_confirmed_event_has_correct_properties(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $event = new BookingConfirmed(
            booking: Booking::first() ?? $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1),
            actor: $scenario['user'],
            paymentChannel: 'mobile-money'
        );

        $this->assertEquals('mobile-money', $event->paymentChannel);
        $this->assertNotNull($event->booking);
    }

    /** @test */
    public function ticket_rescheduled_event_tracks_fare_difference(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = $this->createConfirmedBooking($scenario['voyage'], $scenario['class'], $scenario['user'], 1);

        $event = new TicketRescheduled(
            ticket: $booking->items->first(),
            booking: $booking,
            actor: $scenario['user'],
            oldVoyageId: 1,
            newVoyageId: 2,
            fareDifference: 5000.0,
            wasSplit: false,
        );

        $this->assertEquals(5000.0, $event->fareDifference);
        $this->assertFalse($event->wasSplit);
    }

    // ── Reconciliation Command Tests ─────────────────────────

    /** @test */
    public function reconciliation_command_runs_without_errors(): void
    {
        $this->artisan('financials:reconcile', ['--date' => now()->toDateString()])
            ->assertExitCode(0);
    }

    /** @test */
    public function reconciliation_detects_orphaned_bookings(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        // Create a confirmed booking with NO transactions (orphan)
        Booking::create([
            'booking_reference' => 'ORPHAN-' . uniqid(),
            'status' => 'confirmed',
            'payment_status' => 'paid',
            'net_amount' => 50000,
            'currency' => 'TZS',
            'booked_by_user_id' => $scenario['user']->id,
            'created_at' => now(),
        ]);

        $this->artisan('financials:reconcile', ['--date' => now()->toDateString()])
            ->expectsOutputToContain('orphaned')
            ->assertExitCode(1); // HIGH severity = failure exit code
    }

    // ── Booking Expiry Tests ─────────────────────────────────

    /** @test */
    public function expire_holds_command_only_expires_past_deadline(): void
    {
        $scenario = $this->createCompleteBookingScenario();

        // Create an expired booking
        $expired = Booking::create([
            'booking_reference' => 'EXP-' . uniqid(),
            'status' => 'held',
            'payment_status' => 'unpaid',
            'net_amount' => 35000,
            'currency' => 'TZS',
            'booked_by_user_id' => $scenario['user']->id,
            'expires_at' => now()->subMinutes(5),
        ]);

        // Create a still-valid booking
        $valid = Booking::create([
            'booking_reference' => 'VALID-' . uniqid(),
            'status' => 'held',
            'payment_status' => 'unpaid',
            'net_amount' => 35000,
            'currency' => 'TZS',
            'booked_by_user_id' => $scenario['user']->id,
            'expires_at' => now()->addHours(1),
        ]);

        Event::fake([BookingExpired::class]);
        $this->artisan('bookings:expire-holds')->assertExitCode(0);

        $expired->refresh();
        $valid->refresh();

        $this->assertEquals('expired', $expired->status);
        $this->assertEquals('held', $valid->status);
    }

    /** @test */
    public function expire_holds_dry_run_does_not_change_status(): void
    {
        $scenario = $this->createCompleteBookingScenario();
        $booking = Booking::create([
            'booking_reference' => 'DRY-' . uniqid(),
            'status' => 'held',
            'payment_status' => 'unpaid',
            'net_amount' => 35000,
            'currency' => 'TZS',
            'booked_by_user_id' => $scenario['user']->id,
            'expires_at' => now()->subMinutes(5),
        ]);

        $this->artisan('bookings:expire-holds', ['--dry-run' => true])->assertExitCode(0);

        $booking->refresh();
        $this->assertEquals('held', $booking->status);
    }
}
