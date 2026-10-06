<?php

namespace App\Gateways\Payment\Push;

use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Airtel Money Tanzania — Airtel Africa Open API (developers.airtel.africa).
 *
 * OAuth2 client-credentials token → POST /merchant/v1/payments/ (USSD push, async)
 * → callback to our URL, plus GET /standard/v1/payments/{id} for status enquiry.
 *
 * Credentials: api_key (client id), api_secret (client secret).
 */
class AirtelMoneyGateway extends AbstractPushGateway
{
    public function initiate(CargoPayment $payment): PushResult
    {
        $response = $this->call($payment, 'push', 'POST', $this->baseUrl() . '/merchant/v1/payments/', [
            'reference'  => (string) $payment->consignment?->consignment_number,
            'subscriber' => [
                'country'  => 'TZ',
                'currency' => 'TZS',
                'msisdn'   => substr((string) $payment->payer_msisdn, 3), // without the 255 country code
            ],
            'transaction' => [
                'amount'   => $this->amount($payment),
                'country'  => 'TZ',
                'currency' => 'TZS',
                'id'       => $payment->reference,
            ],
        ], $this->headers($payment));

        if ($response->successful() && $response->json('status.success') === true) {
            return PushResult::pending('Push sent to the customer\'s Airtel Money phone.', $payment->reference, $response->json() ?? []);
        }

        return PushResult::failed($response->json('status.message') ?: 'Airtel Money request failed (HTTP ' . $response->status() . ').', null, $response->json() ?? []);
    }

    public function supportsStatusQuery(): bool
    {
        return true;
    }

    public function queryStatus(CargoPayment $payment): PushResult
    {
        $response = $this->call($payment, 'status', 'GET', $this->baseUrl() . '/standard/v1/payments/' . $payment->reference, [], $this->headers($payment));

        return $this->fromTransaction((array) $response->json('data.transaction'), $response->json() ?? []);
    }

    public function parseCallback(Request $request): ?PushResult
    {
        $transaction = (array) $request->input('transaction');
        if (empty($transaction['id'])) {
            return null;
        }

        return $this->fromTransaction($transaction, $request->all());
    }

    /**
     * TS = success, TF = failed, TIP/TA = in progress / ambiguous.
     */
    private function fromTransaction(array $tx, array $raw): PushResult
    {
        $code = strtoupper((string) ($tx['status_code'] ?? $tx['status'] ?? ''));
        $ours = $tx['id'] ?? null;

        return match ($code) {
            'TS'    => PushResult::paid($tx['airtel_money_id'] ?? null, $ours, null, $raw, $ours),
            'TF'    => PushResult::failed($tx['message'] ?? 'Airtel Money payment failed.', $ours, $raw, $ours),
            default => PushResult::pending($tx['message'] ?? null, $ours, $raw, $ours),
        };
    }

    private function headers(CargoPayment $payment): array
    {
        return [
            'Authorization' => 'Bearer ' . $this->token($payment),
            'X-Country'     => 'TZ',
            'X-Currency'    => 'TZS',
        ];
    }

    private function token(CargoPayment $payment): string
    {
        $key = 'cargo_pay:airtel_token:' . md5($this->config->environment . '|' . $this->config->api_key);

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = $this->call($payment, 'token', 'POST', $this->baseUrl() . '/auth/oauth2/token', [
            'client_id'     => $this->config->api_key,
            'client_secret' => $this->config->api_secret,
            'grant_type'    => 'client_credentials',
        ]);

        $token = $response->json('access_token');
        if (!$token) {
            throw new RuntimeException('Airtel Money authentication failed (HTTP ' . $response->status() . ').');
        }

        Cache::put($key, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 180) - 60)));

        return $token;
    }
}
