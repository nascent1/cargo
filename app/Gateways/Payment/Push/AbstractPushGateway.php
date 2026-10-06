<?php

namespace App\Gateways\Payment\Push;

use App\Contracts\UssdPushGateway;
use App\Models\CargoPayment;
use App\Models\IntegrationConfig;
use App\Models\PaymentGatewayLog;
use Illuminate\Http\Client\Response as HttpResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Shared plumbing for push drivers: credentials from integration_configs, HTTP with
 * timeouts, masked request/response logging to payment_gateway_logs, callback token auth.
 */
abstract class AbstractPushGateway implements UssdPushGateway
{
    private const SENSITIVE_KEY = '/pass|secret|token|authorization|api_?key|public_?key|digest|pin|credential/i';

    public function __construct(protected IntegrationConfig $config, protected string $driver)
    {
    }

    public function supportsStatusQuery(): bool
    {
        return false;
    }

    public function queryStatus(CargoPayment $payment): PushResult
    {
        return PushResult::pending('Status query not supported by this provider.');
    }

    /**
     * Baseline auth: the callback URL we register carries a secret token (?token=…).
     */
    public function verifyCallback(Request $request): bool
    {
        $expected = $this->config->extra('callback_token');

        return filled($expected) && hash_equals((string) $expected, (string) $request->query('token'));
    }

    public function parseCallback(Request $request): ?PushResult
    {
        return null;
    }

    public function callbackAck(bool $ok): Response
    {
        return response()->json(['status' => $ok ? 'ok' : 'rejected']);
    }

    // ── Helpers ─────────────────────────────────────────────

    protected function baseUrl(): string
    {
        $url = $this->config->activeUrl();
        if (!$url) {
            throw new RuntimeException("No {$this->config->environment} URL configured for {$this->config->name}.");
        }

        return $url;
    }

    protected function callbackUrl(): string
    {
        return PushGatewayManager::callbackUrl($this->config, $this->driver);
    }

    /**
     * Whole-shilling amount as most MNO APIs expect.
     */
    protected function amount(CargoPayment $payment): int
    {
        return (int) round((float) $payment->amount);
    }

    /**
     * Perform an HTTP call and always log it (masked), even when it throws.
     */
    protected function call(
        ?CargoPayment $payment,
        string $event,
        string $method,
        string $url,
        array $payload = [],
        array $headers = [],
        int $timeout = 30,
        bool $asForm = false,
    ): HttpResponse {
        $started = microtime(true);
        $response = null;
        $error = null;

        try {
            $request = Http::withHeaders($headers)->timeout($timeout)->acceptJson();
            if ($asForm) {
                $request = $request->asForm();
            }

            $response = strtoupper($method) === 'GET'
                ? $request->get($url, $payload)
                : $request->post($url, $payload);

            return $response;
        } catch (\Throwable $e) {
            $error = $e->getMessage();
            throw $e;
        } finally {
            $this->log($payment, $event, 'outgoing', $method, $url, $headers, $payload, $response, $error, $started);
        }
    }

    protected function log(
        ?CargoPayment $payment,
        string $event,
        string $direction,
        ?string $method,
        ?string $url,
        array $headers,
        array $payload,
        ?HttpResponse $response = null,
        ?string $error = null,
        ?float $started = null,
    ): void {
        try {
            PaymentGatewayLog::create([
                'consignment_id'        => $payment?->consignment_id,
                'cargo_payment_id'      => $payment?->id,
                'gateway'               => $this->driver,
                'event_type'            => $event,
                'direction'             => $direction,
                'http_method'           => $method,
                'url'                   => $url,
                'request_headers'       => $this->mask($headers),
                'request_payload'       => $this->mask($payload),
                'response_payload'      => $response ? $this->mask($response->json() ?? ['body' => mb_substr($response->body(), 0, 2000)]) : null,
                'http_status_code'      => $response?->status(),
                'transaction_reference' => $payment?->reference,
                'amount'                => $payment?->amount,
                'currency'              => $payment?->currency,
                'is_success'            => $error === null && ($response?->successful() ?? false),
                'error_message'         => $error ?? ($response && !$response->successful() ? mb_substr($response->body(), 0, 500) : null),
                'response_time_ms'      => $started ? (int) ((microtime(true) - $started) * 1000) : null,
            ]);
        } catch (\Throwable $e) {
            // Logging must never break a payment
            Log::warning('Failed to write payment gateway log: ' . $e->getMessage());
        }
    }

    protected function mask(array $data): array
    {
        return static::maskData($data);
    }

    /**
     * Replace credential-like values (passwords, tokens, keys, signatures) with ***.
     */
    public static function maskData(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = static::maskData($value);
            } elseif (is_string($key) && preg_match(self::SENSITIVE_KEY, $key)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }
}
