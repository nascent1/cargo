<?php

namespace App\Enums;

/**
 * The two service lines a consignment can belong to (consignments.service_type).
 */
enum ServiceType: string
{
    /** Parcels on the scheduled ferries: received, assigned, boarded, carried, collected. */
    case Parcel = 'parcel';

    /** Contract cargo on the landing craft, carried under a cargo booking. */
    case Cargo = 'cargo';

    public function label(): string
    {
        return match ($this) {
            self::Parcel => 'Parcel',
            self::Cargo => 'Contract cargo',
        };
    }
}
