<?php

namespace App\Services\Cargo;

use App\Enums\ConsignmentStatus as S;
use App\Enums\ServiceType;
use DomainException;

/**
 * The moves a consignment may make between statuses, per service line.
 *
 * One place answers "may this become that?", so screens, scanners, commands and approvals agree.
 * It reads no database and changes nothing: callers ask before they write.
 *
 * Not yet used by the booking flows: Phase 2 (parcels) and Phase 5 (contract cargo) switch them over.
 */
final class ConsignmentStateMachine
{
    /**
     * Every status of the service line, each with the statuses it may move to.
     * The parcel moves follow the diagram in the requirements, section 3.1.
     *
     * @return array<string, list<S>>  keyed by status value
     */
    public static function transitions(ServiceType $service): array
    {
        return match ($service) {
            ServiceType::Parcel => [
                S::PendingPayment->value => [S::Received, S::Cancelled],   // paid, or not paid in time
                S::Received->value       => [S::Assigned, S::Cancelled],   // put in a tray or on a sailing; cancelled before dispatch
                S::Assigned->value       => [S::Boarded, S::Received, S::Cancelled], // scanned at the vessel; removed (space released); cancelled (space released)
                S::Boarded->value        => [S::InTransit, S::Assigned],   // vessel departs; taken off before departure
                S::InTransit->value      => [S::Arrived],
                S::Arrived->value        => [S::Collected, S::Unclaimed],
                S::Collected->value      => [],
                S::Unclaimed->value      => [S::Collected, S::Returned],
                S::Returned->value       => [],
                S::Cancelled->value      => [],
            ],
            ServiceType::Cargo => [
                S::Expected->value   => [S::CheckedIn, S::Cancelled],
                S::CheckedIn->value  => [S::Loaded, S::Cancelled],
                S::Loaded->value     => [S::InTransit, S::CheckedIn],      // vessel departs; taken off before departure
                S::InTransit->value  => [S::Arrived],
                S::Arrived->value    => [S::Discharged],
                S::Discharged->value => [S::Delivered],
                S::Delivered->value  => [],
                S::Cancelled->value  => [],
            ],
        };
    }

    /**
     * @return list<S>
     */
    public static function allowedFrom(S $from, ServiceType $service = ServiceType::Parcel): array
    {
        return self::transitions($service)[$from->value] ?? [];
    }

    public static function canMove(S $from, S $to, ServiceType $service = ServiceType::Parcel): bool
    {
        return in_array($to, self::allowedFrom($from, $service), true);
    }

    /**
     * @throws DomainException when the move is not allowed, with a message fit to show the user
     */
    public static function assertCanMove(S $from, S $to, ServiceType $service = ServiceType::Parcel): void
    {
        if (self::canMove($from, $to, $service)) {
            return;
        }

        $allowed = array_map(fn (S $status) => $status->label(), self::allowedFrom($from, $service));
        $what = $service === ServiceType::Parcel ? 'A parcel' : 'A cargo consignment';

        throw new DomainException(sprintf(
            '%s that is "%s" cannot become "%s". %s',
            $what,
            $from->label(),
            $to->label(),
            $allowed ? 'It can become: ' . implode(', ', $allowed) . '.' : 'It is final.'
        ));
    }

    /** No further move is possible from this status. */
    public static function isFinal(S $status, ServiceType $service = ServiceType::Parcel): bool
    {
        return self::allowedFrom($status, $service) === [];
    }
}
