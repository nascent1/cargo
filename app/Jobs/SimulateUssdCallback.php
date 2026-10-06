<?php

namespace App\Jobs;

use App\Models\CargoPayment;
use App\Services\Cargo\CargoPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Local/dev only: plays the provider's callback for a simulated push.
 */
class SimulateUssdCallback implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public function __construct(public int $paymentId)
    {
    }

    public function handle(CargoPaymentService $payments): void
    {
        if ($payment = CargoPayment::find($this->paymentId)) {
            $payments->refreshStatus($payment);
        }
    }
}
