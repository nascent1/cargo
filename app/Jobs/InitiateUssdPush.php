<?php

namespace App\Jobs;

use App\Models\CargoPayment;
use App\Services\Cargo\CargoPaymentService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;

/**
 * Sends a USSD push off the request cycle (Vodacom's C2B call blocks until the PIN is entered).
 */
class InitiateUssdPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    // Never retry automatically: a retry would prompt the customer a second time
    public int $tries = 1;

    public int $timeout = 180;

    public function __construct(public int $paymentId)
    {
    }

    public function handle(CargoPaymentService $payments): void
    {
        if ($payment = CargoPayment::find($this->paymentId)) {
            $payments->sendPush($payment);
        }
    }
}
