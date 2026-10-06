<?php

namespace App\Gateways\Payment\Push;

use App\Models\CargoPayment;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Selcom Pesa — Selcom Checkout API (developers.selcommobile.com).
 *
 * create-order-minimal → wallet-payment (USSD push to the msisdn) → webhook,
 * plus order-status for enquiry. Requests are HMAC-SHA256 signed over Signed-Fields.
 *
 * Credentials: api_key, api_secret, merchant_id (vendor id).
 */
class SelcomPesaGateway extends AbstractPushGateway
{
    public function initiate(CargoPayment $payment): PushResult
    {
        $consignment = $payment->consignment;

        $order = $this->signedCall($payment, 'order', 'POST', '/v1/checkout/create-order-minimal', [
            'vendor'      => (string) $this->config->merchant_id,
            'order_id'    => $payment->reference,
            'buyer_email' => $consignment?->sender_email ?: 'noreply@' . parse_url(config('app.url'), PHP_URL_HOST),
            'buyer_name'  => $consignment?->sender_name ?: 'Cargo Customer',
            'buyer_phone' => $payment->payer_msisdn,
            'amount'      => $this->amount($payment),
            'currency'    => 'TZS',
            'webhook'     => base64_encode($this->callbackUrl()),
            'no_of_items' => max(1, (int) $consignment?->items()->count()),
        ]);

        if ($order->json('result') !== 'SUCCESS') {
            return PushResult::failed($order->json('message') ?: 'Selcom order creation failed (HTTP ' . $order->status() . ').', null, $order->json() ?? []);
        }

        $push = $this->signedCall($payment, 'push', 'POST', '/v1/checkout/wallet-payment', [
            'transid'  => $payment->reference,
            'order_id' => $payment->reference,
            'msisdn'   => $payment->payer_msisdn,
        ]);

        if ($push->json('result') === 'SUCCESS') {
            return PushResult::pending($push->json('message'), $payment->reference, $push->json() ?? []);
        }

        return PushResult::failed($push->json('message') ?: 'Selcom push failed (HTTP ' . $push->status() . ').', $payment->reference, $push->json() ?? []);
    }

    public function supportsStatusQuery(): bool
    {
        return true;
    }

    public function queryStatus(CargoPayment $payment): PushResult
    {
        $response = $this->signedCall($payment, 'status', 'GET', '/v1/checkout/order-status', ['order_id' => $payment->reference]);
        $order = (array) ($response->json('data.0') ?? []);

        return $this->fromOrder($order, $response->json() ?? []);
    }

    /**
     * Valid when our callback token matches OR Selcom's digest over Signed-Fields checks out.
     */
    public function verifyCallback(Request $request): bool
    {
        if (parent::verifyCallback($request)) {
            return true;
        }

        $digest = (string) $request->header('Digest');
        $timestamp = (string) $request->header('Timestamp');
        $fields = array_filter(explode(',', (string) $request->header('Signed-Fields')));
        if ($digest === '' || $timestamp === '' || !$fields) {
            return false;
        }

        $data = 'timestamp=' . $timestamp;
        foreach ($fields as $field) {
            $data .= '&' . $field . '=' . data_get($request->all(), trim($field));
        }

        $expected = base64_encode(hash_hmac('sha256', $data, (string) $this->config->api_secret, true));

        return hash_equals($expected, $digest);
    }

    public function parseCallback(Request $request): ?PushResult
    {
        if (!$request->input('order_id')) {
            return null;
        }

        return $this->fromOrder($request->all(), $request->all());
    }

    public function callbackAck(bool $ok): Response
    {
        return response()->json(['result' => $ok ? 'SUCCESS' : 'FAIL', 'resultcode' => $ok ? '000' : '999']);
    }

    /**
     * payment_status: COMPLETED | PENDING | INPROGRESS | CANCELLED | USERCANCELED | REJECTED
     */
    private function fromOrder(array $order, array $raw): PushResult
    {
        $status = strtoupper((string) ($order['payment_status'] ?? ''));
        $ours = $order['order_id'] ?? null;

        return match (true) {
            $status === 'COMPLETED' => PushResult::paid($order['reference'] ?? $order['transid'] ?? null, $ours, isset($order['amount']) ? (float) $order['amount'] : null, $raw, $ours),
            in_array($status, ['CANCELLED', 'USERCANCELED', 'REJECTED', 'FAILED'], true) => PushResult::failed('Selcom reports the payment as ' . strtolower($status) . '.', $ours, $raw, $ours),
            default => PushResult::pending(null, $ours, $raw, $ours),
        };
    }

    private function signedCall(CargoPayment $payment, string $event, string $method, string $path, array $params)
    {
        $timestamp = now()->format('c');
        $data = 'timestamp=' . $timestamp;
        foreach ($params as $key => $value) {
            $data .= '&' . $key . '=' . $value;
        }

        return $this->call($payment, $event, $method, $this->baseUrl() . $path, $params, [
            'Authorization' => 'SELCOM ' . base64_encode((string) $this->config->api_key),
            'Digest-Method' => 'HS256',
            'Digest'        => base64_encode(hash_hmac('sha256', $data, (string) $this->config->api_secret, true)),
            'Timestamp'     => $timestamp,
            'Signed-Fields' => implode(',', array_keys($params)),
        ]);
    }
}
