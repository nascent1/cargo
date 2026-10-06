<?php

namespace App\Contracts;

use App\Gateways\Payment\Push\PushResult;
use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * A mobile-money integration that can push a USSD payment prompt to the payer's phone.
 */
interface UssdPushGateway
{
    /**
     * Send the push. Async providers return PENDING; synchronous ones (Vodacom C2B)
     * return the final PAID/FAILED outcome.
     */
    public function initiate(CargoPayment $payment): PushResult;

    /**
     * Ask the provider for the current status (PENDING when unknown or unsupported).
     */
    public function queryStatus(CargoPayment $payment): PushResult;

    public function supportsStatusQuery(): bool;

    /**
     * Authenticate an incoming callback (shared callback token and/or provider signature).
     */
    public function verifyCallback(Request $request): bool;

    /**
     * Extract the outcome from a callback; ourReference / providerRequestId locate the attempt.
     */
    public function parseCallback(Request $request): ?PushResult;

    /**
     * Acknowledgement body in the format the provider expects.
     */
    public function callbackAck(bool $ok): Response;
}
