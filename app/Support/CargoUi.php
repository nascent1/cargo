<?php

namespace App\Support;

/**
 * Status and payment badge vocabulary shared by the admin portal (CargoUI in admin/cargo/partials/ui)
 * and the sales portal (window.CargoUiConfig in portal/spa), so a badge reads the same everywhere.
 */
final class CargoUi
{
    public const STATUS = [
        'pending_payment' => ['label' => 'Awaiting Payment', 'cls' => 'bg-orange-100 text-orange-700 border-orange-200'],
        'booked'          => ['label' => 'Booked',           'cls' => 'bg-amber-100 text-amber-700 border-amber-200'],
        'loaded'          => ['label' => 'Loaded',           'cls' => 'bg-blue-100 text-blue-700 border-blue-200'],
        'in_transit'      => ['label' => 'In Transit',       'cls' => 'bg-violet-100 text-violet-700 border-violet-200'],
        'delivered'       => ['label' => 'Delivered',        'cls' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
        'unclaimed'       => ['label' => 'Unclaimed',        'cls' => 'bg-rose-50 text-rose-700 border-rose-200'],
        'collected'       => ['label' => 'Collected',        'cls' => 'bg-teal-100 text-teal-700 border-teal-200'],
        'cancelled'       => ['label' => 'Cancelled',        'cls' => 'bg-red-100 text-red-700 border-red-200'],
    ];

    public const PAYMENT = [
        'paid'   => ['label' => 'Paid',   'cls' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'icon' => 'fa-check'],
        'unpaid' => ['label' => 'Unpaid', 'cls' => 'bg-surface-100 text-surface-600 border-surface-300', 'icon' => 'fa-clock'],
    ];

    public static function config(): array
    {
        return ['status' => self::STATUS, 'payment' => self::PAYMENT];
    }
}
