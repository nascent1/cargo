<?php

namespace App\Gateways\Payment\Push;

use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Halopesa (Halotel) — USSD push collection.
 *
 * Halotel shares its API document under the merchant agreement, so this adapter uses a
 * conventional token → push → callback shape with configurable paths and tolerant callback
 * parsing. VERIFY field names against your Halopesa integration document before go-live.
 *
 * Credentials: api_key (username / client id), api_secret (password / secret),
 * merchant_id (business number), extra.token_path, extra.push_path.
 */
class HalopesaGateway extends AbstractPushGateway
{
    public function initiate(CargoPayment $payment): PushResult
    {
        $response = $this->call($payment, 'push', 'POST', $this->baseUrl() . $this->config->extra('push_path'), [
            'merchantId'  => (string) $this->config->merchant_id,
            'msisdn'      => $payment->payer_msisdn,
            'amount'      => $this->amount($payment),
            'currency'    => 'TZS',
            'reference'   => $payment->reference,
            'description' => 'Cargo ' . $payment->consignment?->consignment_number,
            'callbackUrl' => $this->callbackUrl(),
        ], ['Authorization' => 'Bearer ' . $this->token($payment)], 60);

        $status = strtolower((string) ($response->json('status') ?? $response->json('responseStatus') ?? ''));
        $accepted = $response->successful()
            && ($response->json('success') === true || in_array($status, ['success', 'accepted', 'pending', 'processing', '0', '000'], true));

        if ($accepted) {
            return PushResult::pending($response->json('message'), $response->json('transactionId') ?? $payment->reference, $response->json() ?? []);
        }

        return PushResult::failed($response->json('message') ?: 'Halopesa request failed (HTTP ' . $response->status() . ').', null, $response->json() ?? []);
    }

    public function parseCallback(Request $request): ?PushResult
    {
        $ours = $request->input('reference') ?? $request->input('referenceId') ?? $request->input('reference_id');
        if (!$ours) {
            return null;
        }

        $status = strtolower((string) ($request->input('status') ?? $request->input('transactionStatus') ?? ''));
        $receipt = $request->input('transactionId') ?? $request->input('transaction_id') ?? $request->input('receipt');

        if (in_array($status, ['success', 'successful', 'completed', 'paid', '0', '000'], true)) {
            return PushResult::paid($receipt, null, (float) $request->input('amount') ?: null, $request->all(), $ours);
        }

        if (in_array($status, ['pending', 'processing'], true)) {
            return PushResult::pending(null, null, $request->all(), $ours);
        }

        return PushResult::failed($request->input('message') ?: 'Halopesa payment failed.', null, $request->all(), $ours);
    }

    private function token(CargoPayment $payment): string
    {
        $key = 'cargo_pay:halopesa_token:' . md5($this->config->environment . '|' . $this->config->api_key);

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = $this->call($payment, 'token', 'POST', $this->baseUrl() . $this->config->extra('token_path'), [
            'username' => $this->config->api_key,
            'password' => $this->config->api_secret,
        ]);

        $token = $response->json('access_token') ?? $response->json('token') ?? $response->json('data.token');
        if (!$token) {
            throw new RuntimeException('Halopesa authentication failed (HTTP ' . $response->status() . ').');
        }

        Cache::put($key, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 1800) - 60)));

        return $token;
    }
}
