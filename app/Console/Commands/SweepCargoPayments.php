<?php

namespace App\Console\Commands;

use App\Services\Cargo\CargoPaymentService;
use Illuminate\Console\Command;

class SweepCargoPayments extends Command
{
    protected $signature = 'cargo:payments-sweep';

    protected $description = 'Send stalled USSD pushes, re-check quiet ones, expire overdue payments and auto-cancel unpaid bookings';

    public function handle(CargoPaymentService $payments): int
    {
        $stats = $payments->sweep();

        $this->info(sprintf(
            'Pushed %d, re-checked %d, expired %d, auto-cancelled %d booking(s).',
            $stats['pushed'], $stats['refreshed'], $stats['expired'], $stats['cancelled'],
        ));

        return self::SUCCESS;
    }
}
