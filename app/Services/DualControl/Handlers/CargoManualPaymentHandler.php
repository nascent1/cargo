<?php

namespace App\Services\DualControl\Handlers;

use App\Exceptions\CargoPaymentException;
use App\Models\CargoPayment;
use App\Models\DualControlRequest;
use App\Models\User;
use App\Services\Cargo\CargoPaymentService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use Carbon\CarbonInterface;

/**
 * Marks a Lipa Namba / cash payment as received with its reference, recorded as the maker's confirmation.
 */
class CargoManualPaymentHandler implements DualControlHandler
{
    public function __construct(private CargoPaymentService $payments)
    {
    }

    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $payment = CargoPayment::find($request->resource_id) ?? throw new DualControlException('The payment no longer exists.');
        $maker = User::find($request->maker_id) ?? throw new DualControlException('The person who submitted this no longer exists.');
        $after = $request->payload_after ?? [];

        try {
            $this->payments->confirmManual($payment, (string) ($after['reference'] ?? ''), $after['payer_msisdn'] ?? null, $maker);
        } catch (CargoPaymentException $e) {
            // Submitted with the rule off, the caller shows this the way it always did
            if ($request->checker_id === $request->maker_id) {
                throw $e;
            }
            throw new DualControlException($e->getMessage());
        }
    }
}
