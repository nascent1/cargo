<?php

namespace App\Gateways\Payment\Push;

use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Tigo Pesa (Mixx by Yas) — Push Bill Pay.
 *
 * Token (username/password grant) → BillerPay push → async callback to our URL.
 * Paths and the reference prefix are configurable because Tigo issues the API
 * document per merchant. VERIFY against your Tigo integration document before go-live.
 *
 * Credentials: extra.username, extra.password, merchant_id (biller MSISDN),
 * extra.biller_code (reference prefix), extra.token_path, extra.push_path.
 */
class TigoPesaGateway extends AbstractPushGateway
{
    public function initiate(CargoPayment $payment): PushResult
    {
        $referenceId = $this->config->extra('biller_code', '') . $payment->reference;

        $response = $this->call($payment, 'push', 'POST', $this->baseUrl() . $this->config->extra('push_path', '/API/BillerPayments/BillerPay'), [
            'CustomerMSISDN' => $payment->payer_msisdn,
            'BillerMSISDN'   => (string) $this->config->merchant_id,
            'Amount'         => $this->amount($payment),
            'Remarks'        => 'Cargo ' . $payment->consignment?->consignment_number,
            'ReferenceID'    => $referenceId,
        ], [
            'Authorization' => 'bearer ' . $this->token($payment),
            'Username'      => (string) $this->config->extra('username'),
            'Password'      => (string) $this->config->extra('password'),
            'Cache-Control' => 'no-cache',
        ], 60);

        if ($response->successful() && filter_var($response->json('ResponseStatus'), FILTER_VALIDATE_BOOLEAN)) {
            return PushResult::pending($response->json('ResponseDescription'), $referenceId, $response->json() ?? []);
        }

        return PushResult::failed($response->json('ResponseDescription') ?: 'Tigo Pesa request failed (HTTP ' . $response->status() . ').', null, $response->json() ?? []);
    }

    /**
     * Callback: { Status, Description, MFSTransactionID, ReferenceID, Amount }
     */
    public function parseCallback(Request $request): ?PushResult
    {
        $referenceId = (string) $request->input('ReferenceID');
        if ($referenceId === '') {
            return null;
        }

        $prefix = (string) $this->config->extra('biller_code', '');
        $ours = $prefix !== '' && str_starts_with($referenceId, $prefix) ? substr($referenceId, strlen($prefix)) : $referenceId;

        if (filter_var($request->input('Status'), FILTER_VALIDATE_BOOLEAN)) {
            return PushResult::paid($request->input('MFSTransactionID'), $referenceId, (float) $request->input('Amount') ?: null, $request->all(), $ours);
        }

        return PushResult::failed($request->input('Description') ?: 'Tigo Pesa payment failed.', $referenceId, $request->all(), $ours);
    }

    public function callbackAck(bool $ok): Response
    {
        return response()->json([
            'ResponseCode'        => $ok ? 'BILLER-18-0000-S' : 'BILLER-18-3020-E',
            'ResponseStatus'      => $ok,
            'ResponseDescription' => $ok ? 'Callback Successful' : 'Callback Rejected',
        ]);
    }

    private function token(CargoPayment $payment): string
    {
        $key = 'cargo_pay:tigo_token:' . md5($this->config->environment . '|' . $this->config->extra('username'));

        if ($token = Cache::get($key)) {
            return $token;
        }

        $response = $this->call($payment, 'token', 'POST', $this->baseUrl() . $this->config->extra('token_path', '/token'), [
            'username'   => $this->config->extra('username'),
            'password'   => $this->config->extra('password'),
            'grant_type' => 'password',
        ], [], 30, true);

        $token = $response->json('access_token');
        if (!$token) {
            throw new RuntimeException('Tigo Pesa authentication failed (HTTP ' . $response->status() . ').');
        }

        Cache::put($key, $token, now()->addSeconds(max(60, (int) $response->json('expires_in', 3600) - 60)));

        return $token;
    }
}
