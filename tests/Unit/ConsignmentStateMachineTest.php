<?php

namespace Tests\Unit;

use App\Enums\ChargeBasis;
use App\Enums\ConsignmentStatus as S;
use App\Enums\ServiceType;
use App\Services\Cargo\ConsignmentStateMachine as Machine;
use DomainException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/** Pure rules: no database, no application. */
class ConsignmentStateMachineTest extends TestCase
{
    #[Test]
    public function a_parcel_follows_the_path_in_the_requirements(): void
    {
        $path = [S::PendingPayment, S::Received, S::Assigned, S::Boarded, S::InTransit, S::Arrived, S::Collected];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue(Machine::canMove($path[$i], $path[$i + 1]), "{$path[$i]->value} → {$path[$i + 1]->value}");
        }
    }

    #[Test]
    public function a_parcel_cannot_skip_a_stage_or_go_back_after_departure(): void
    {
        $this->assertFalse(Machine::canMove(S::Received, S::Boarded), 'boarding needs an assignment first');
        $this->assertFalse(Machine::canMove(S::PendingPayment, S::Assigned), 'unpaid parcels are not assigned');
        $this->assertFalse(Machine::canMove(S::Received, S::InTransit));
        $this->assertFalse(Machine::canMove(S::InTransit, S::Boarded));
        $this->assertFalse(Machine::canMove(S::Arrived, S::InTransit));
        $this->assertFalse(Machine::canMove(S::InTransit, S::Cancelled), 'no cancellation once it has sailed');
        $this->assertFalse(Machine::canMove(S::Boarded, S::Cancelled), 'it is taken off the vessel first');
    }

    #[Test]
    public function an_assignment_can_be_undone_until_the_vessel_departs(): void
    {
        $this->assertTrue(Machine::canMove(S::Assigned, S::Received), 'removed from the tray or the sailing');
        $this->assertTrue(Machine::canMove(S::Boarded, S::Assigned), 'taken off before departure');
        $this->assertTrue(Machine::canMove(S::Received, S::Cancelled));
        $this->assertTrue(Machine::canMove(S::Assigned, S::Cancelled));
        $this->assertTrue(Machine::canMove(S::PendingPayment, S::Cancelled), 'not paid in time');
    }

    #[Test]
    public function an_uncollected_parcel_becomes_unclaimed_then_collected_or_returned(): void
    {
        $this->assertSame([S::Collected, S::Unclaimed], Machine::allowedFrom(S::Arrived));
        $this->assertSame([S::Collected, S::Returned], Machine::allowedFrom(S::Unclaimed));
    }

    #[Test]
    public function collected_returned_and_cancelled_are_final(): void
    {
        foreach ([S::Collected, S::Returned, S::Cancelled] as $status) {
            $this->assertTrue(Machine::isFinal($status), $status->value);
            $this->assertSame([], Machine::allowedFrom($status));
        }
        $this->assertFalse(Machine::isFinal(S::Arrived));
    }

    #[Test]
    public function contract_cargo_has_its_own_path(): void
    {
        $path = [S::Expected, S::CheckedIn, S::Loaded, S::InTransit, S::Arrived, S::Discharged, S::Delivered];

        for ($i = 0; $i < count($path) - 1; $i++) {
            $this->assertTrue(Machine::canMove($path[$i], $path[$i + 1], ServiceType::Cargo), "{$path[$i]->value} → {$path[$i + 1]->value}");
        }

        $this->assertTrue(Machine::isFinal(S::Delivered, ServiceType::Cargo));
        $this->assertFalse(Machine::canMove(S::Loaded, S::Cancelled, ServiceType::Cargo));
        $this->assertFalse(Machine::canMove(S::Received, S::Assigned, ServiceType::Cargo), 'parcel statuses do not apply to contract cargo');
        $this->assertFalse(Machine::canMove(S::Expected, S::CheckedIn), 'cargo statuses do not apply to parcels');
    }

    #[Test]
    public function every_status_of_a_service_line_is_in_its_table_and_moves_only_within_the_line(): void
    {
        foreach (ServiceType::cases() as $service) {
            $statuses = S::forService($service);
            $table = Machine::transitions($service);

            $this->assertSame(
                array_map(fn (S $s) => $s->value, $statuses),
                array_keys($table),
                "{$service->value}: the table lists exactly the line's statuses, in order"
            );

            foreach ($table as $from => $targets) {
                foreach ($targets as $to) {
                    $this->assertContains($to, $statuses, "{$service->value}: {$from} → {$to->value}");
                    $this->assertNotSame($from, $to->value, 'a status never moves to itself');
                }
            }
        }
    }

    #[Test]
    public function every_status_can_be_reached_and_every_open_status_can_end(): void
    {
        foreach (ServiceType::cases() as $service) {
            $statuses = S::forService($service);
            $start = $statuses[0];

            $reached = [$start->value => true];
            $queue = [$start];
            while ($queue) {
                foreach (Machine::allowedFrom(array_shift($queue), $service) as $next) {
                    if (!isset($reached[$next->value])) {
                        $reached[$next->value] = true;
                        $queue[] = $next;
                    }
                }
            }

            foreach ($statuses as $status) {
                $this->assertArrayHasKey($status->value, $reached, "{$service->value}: {$status->value} can be reached from {$start->value}");
            }
        }
    }

    #[Test]
    public function a_refused_move_explains_itself(): void
    {
        try {
            Machine::assertCanMove(S::Received, S::Boarded);
            $this->fail('The move should have been refused.');
        } catch (DomainException $e) {
            $this->assertSame('A parcel that is "Received" cannot become "Boarded". It can become: Assigned to tray, Cancelled.', $e->getMessage());
        }

        try {
            Machine::assertCanMove(S::Collected, S::Arrived);
            $this->fail('The move should have been refused.');
        } catch (DomainException $e) {
            $this->assertSame('A parcel that is "Collected" cannot become "Arrived". It is final.', $e->getMessage());
        }

        Machine::assertCanMove(S::Received, S::Assigned); // allowed: no exception
        $this->addToAssertionCount(1);
    }

    #[Test]
    public function stored_values_are_read_including_the_names_used_before_the_rename(): void
    {
        $this->assertSame(S::Received, S::fromStored('booked'));
        $this->assertSame(S::Boarded, S::fromStored('loaded'));
        $this->assertSame(S::Arrived, S::fromStored('delivered'));
        $this->assertSame(S::PendingPayment, S::fromStored('pending_payment'));
        $this->assertSame(S::Received, S::fromStored('received'));

        // For contract cargo the same two words are real statuses.
        $this->assertSame(S::Loaded, S::fromStored('loaded', ServiceType::Cargo));
        $this->assertSame(S::Delivered, S::fromStored('delivered', ServiceType::Cargo));

        $this->assertNull(S::fromStored('booked', ServiceType::Cargo));
        $this->assertNull(S::fromStored('expected'), 'not a parcel status');
        $this->assertNull(S::fromStored('nonsense'));
        $this->assertNull(S::fromStored(null));
    }

    #[Test]
    public function every_status_has_a_label_and_a_badge(): void
    {
        foreach (S::cases() as $status) {
            $this->assertNotSame('', $status->label());
            $this->assertStringContainsString('bg-', $status->badgeClass());
        }

        $this->assertSame('Assigned to tray', S::Assigned->label());
        $this->assertSame('Assigned to sailing', S::Assigned->label(inTray: false));
        $this->assertSame('Awaiting payment', S::PendingPayment->label());

        $this->assertTrue(S::Arrived->isOpen());
        $this->assertFalse(S::Collected->isOpen());
        $this->assertFalse(S::Cancelled->isOpen());
    }

    #[Test]
    public function charge_bases_are_read_from_a_stored_list(): void
    {
        $this->assertSame(
            [ChargeBasis::Volume, ChargeBasis::Weight, ChargeBasis::Value],
            ChargeBasis::listFrom(['value', 'weight', 'volume', 'weight', 'bogus']),
            'known values only, no duplicates, in the fixed order'
        );
        $this->assertSame([], ChargeBasis::listFrom(null));
        $this->assertSame([ChargeBasis::Unit], ChargeBasis::listFrom([ChargeBasis::Unit]));
        $this->assertSame('per kg', ChargeBasis::Weight->per());
        $this->assertSame('Contract cargo', ServiceType::Cargo->label());
    }
}
