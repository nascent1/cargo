<?php

namespace App\Enums;

/**
 * Every status a consignment can have (consignments.status), defined once.
 *
 * Screens, reports, SMS and tracking read labels and colours from here. The allowed moves
 * between statuses are in App\Services\Cargo\ConsignmentStateMachine.
 *
 * Not yet used by the booking flows: Phase 2 converts the stored values and switches the flows over.
 */
enum ConsignmentStatus: string
{
    // ── Parcel ───────────────────────────────────────────────
    case PendingPayment = 'pending_payment';
    case Received = 'received';
    case Assigned = 'assigned';
    case Boarded = 'boarded';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Collected = 'collected';
    case Unclaimed = 'unclaimed';
    case Returned = 'returned';
    case Cancelled = 'cancelled';

    // ── Contract cargo only ──────────────────────────────────
    case Expected = 'expected';
    case CheckedIn = 'checked_in';
    case Loaded = 'loaded';
    case Discharged = 'discharged';
    case Delivered = 'delivered';

    /**
     * Parcel values stored before the rename (decision D-09), and what each becomes.
     * "loaded" and "delivered" stay valid for contract cargo, so the service type decides.
     */
    public const LEGACY_PARCEL = [
        'booked' => self::Received,
        'loaded' => self::Boarded,
        'delivered' => self::Arrived,
    ];

    /**
     * The statuses of one service line, in the order a consignment passes through them.
     *
     * @return list<self>
     */
    public static function forService(ServiceType $service): array
    {
        return match ($service) {
            ServiceType::Parcel => [
                self::PendingPayment, self::Received, self::Assigned, self::Boarded, self::InTransit,
                self::Arrived, self::Collected, self::Unclaimed, self::Returned, self::Cancelled,
            ],
            ServiceType::Cargo => [
                self::Expected, self::CheckedIn, self::Loaded, self::InTransit,
                self::Arrived, self::Discharged, self::Delivered, self::Cancelled,
            ],
        };
    }

    /**
     * Reads a stored value, including the parcel names used before the rename.
     * Returns null for a value that is not a status of that service line.
     */
    public static function fromStored(?string $value, ServiceType $service = ServiceType::Parcel): ?self
    {
        if ($value === null) {
            return null;
        }

        $status = $service === ServiceType::Parcel && isset(self::LEGACY_PARCEL[$value])
            ? self::LEGACY_PARCEL[$value]
            : self::tryFrom($value);

        return $status !== null && in_array($status, self::forService($service), true) ? $status : null;
    }

    /**
     * @param  bool  $inTray  for Assigned: a parcel in a tray, or a big parcel or vehicle assigned to a sailing
     */
    public function label(bool $inTray = true): string
    {
        return match ($this) {
            self::PendingPayment => 'Awaiting payment',
            self::Received => 'Received',
            self::Assigned => $inTray ? 'Assigned to tray' : 'Assigned to sailing',
            self::Boarded => 'Boarded',
            self::InTransit => 'In transit',
            self::Arrived => 'Arrived',
            self::Collected => 'Collected',
            self::Unclaimed => 'Unclaimed',
            self::Returned => 'Returned',
            self::Cancelled => 'Cancelled',
            self::Expected => 'Expected',
            self::CheckedIn => 'Checked in',
            self::Loaded => 'Loaded',
            self::Discharged => 'Discharged',
            self::Delivered => 'Delivered',
        };
    }

    /** Badge colours, matching the vocabulary in App\Support\CargoUi. */
    public function badgeClass(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-orange-100 text-orange-700 border-orange-200',
            self::Received, self::Expected => 'bg-amber-100 text-amber-700 border-amber-200',
            self::Assigned, self::CheckedIn => 'bg-sky-100 text-sky-700 border-sky-200',
            self::Boarded, self::Loaded => 'bg-blue-100 text-blue-700 border-blue-200',
            self::InTransit => 'bg-violet-100 text-violet-700 border-violet-200',
            self::Arrived, self::Discharged => 'bg-emerald-100 text-emerald-700 border-emerald-200',
            self::Collected, self::Delivered => 'bg-teal-100 text-teal-700 border-teal-200',
            self::Unclaimed => 'bg-rose-50 text-rose-700 border-rose-200',
            self::Returned => 'bg-surface-100 text-surface-700 border-surface-300',
            self::Cancelled => 'bg-red-100 text-red-700 border-red-200',
        };
    }

    /** The carrier still holds the goods, or is about to: not yet handed over, returned or cancelled. */
    public function isOpen(): bool
    {
        return !in_array($this, [self::Collected, self::Delivered, self::Returned, self::Cancelled], true);
    }
}
