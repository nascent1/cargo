<?php

namespace App\Gateways\Payment\Push;

use App\Contracts\UssdPushGateway;
use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Local/dev stand-in for an unconfigured USSD push channel (never used in production).
 *
 * The "customer" answers DELAY_SECONDS after the push: a payer number ending in 0
 * simulates insufficient balance, anything else pays. The outcome is deterministic,
 * so the callback job, poll refresh and sweep all agree.
 */
class SimulatorGateway implements UssdPushGateway
{
    public const DELAY_SECONDS = 8;

    public function __construct(protected string $simulatedDriver)
    {
    }

    public function initiate(CargoPayment $payment): PushResult
    {
        return PushResult::pending('Simulated push sent (no real money moves).', 'SIM-' . strtoupper(Str::random(10)));
    }

    public function supportsStatusQuery(): bool
    {
        return true;
    }

    public function queryStatus(CargoPayment $payment): PushResult
    {
        $pushedAt = $payment->metadata['pushed_at'] ?? null;
        if (!$pushedAt || now()->lt(Carbon::parse($pushedAt)->addSeconds(self::DELAY_SECONDS))) {
            return PushResult::pending();
        }

        if (str_ends_with((string) $payment->payer_msisdn, '0')) {
            return PushResult::failed('Insufficient balance (simulated: payer number ends in 0).', $payment->provider_request_id);
        }

        return PushResult::paid('SIM' . strtoupper(substr(md5($payment->reference), 0, 10)), $payment->provider_request_id, (float) $payment->amount);
    }

    public function verifyCallback(Request $request): bool
    {
        return false;
    }

    public function parseCallback(Request $request): ?PushResult
    {
        return null;
    }

    public function callbackAck(bool $ok): Response
    {
        return response()->json(['status' => $ok ? 'ok' : 'rejected']);
    }
}
