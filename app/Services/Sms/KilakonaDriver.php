<?php

namespace App\Services\Sms;

use App\Models\IntegrationConfig;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Kilakona vendor messaging API (same request the original SmsService made).
 */
class KilakonaDriver implements SmsDriver
{
    public function __construct(protected IntegrationConfig $config)
    {
    }

    public function send(string $phone, string $message, ?string $reference = null): SmsResult
    {
        $url = $this->config->activeUrl();
        if (!$url || blank($this->config->api_key) || blank($this->config->api_secret)) {
            return SmsResult::failed('Kilakona is missing its URL or API credentials.', 'kilakona', $this->config->id);
        }

        try {
            // TLS verification was already disabled in the original integration; kept to avoid breaking it.
            $response = Http::withoutVerifying()
                ->timeout(15)->connectTimeout(5)
                ->withHeaders([
                    'api_key'    => $this->config->api_key,
                    'api_secret' => $this->config->api_secret,
                ])
                ->asJson()
                ->post($url, [
                    'senderId'    => $this->config->extra('sender_id'),
                    'messageType' => 'text',
                    'message'     => $message,
                    'contacts'    => $phone,
                ]);
        } catch (Throwable $e) {
            return SmsResult::failed('Kilakona request failed: ' . $e->getMessage(), 'kilakona', $this->config->id);
        }

        $body = $response->json() ?? $response->body();
        $bodyFailed = is_array($body) && (
            (array_key_exists('success', $body) && $body['success'] === false)
            || in_array(strtolower((string) ($body['status'] ?? '')), ['error', 'failed', 'fail'], true)
        );

        if (!$response->successful() || $bodyFailed) {
            $error = is_array($body) ? ($body['message'] ?? $body['error'] ?? json_encode($body)) : (string) $body;

            return SmsResult::failed('Kilakona HTTP ' . $response->status() . ': ' . mb_strimwidth((string) $error, 0, 300, '…'), 'kilakona', $this->config->id, $response->status(), $body);
        }

        $messageId = is_array($body) ? ($body['data']['messageId'] ?? $body['messageId'] ?? $body['data']['id'] ?? null) : null;

        return new SmsResult(true, 'kilakona', $this->config->id, $messageId ? (string) $messageId : null, null, $response->status(), $body);
    }
}
