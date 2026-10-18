<?php

namespace App\Services\Sms;

use App\Models\IntegrationConfig;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * NextSMS (messaging-service.co.tz) v2 API: POST /api/sms/v2/text/multi with a bearer token.
 * Numbers go out as 255… without the plus.
 */
class NextSmsDriver implements SmsDriver
{
    private const PATH = '/api/sms/v2/text/multi';

    public function __construct(protected IntegrationConfig $config)
    {
    }

    public function send(string $phone, string $message, ?string $reference = null): SmsResult
    {
        $base = $this->config->activeUrl();
        if (!$base || blank($this->config->access_token)) {
            return SmsResult::failed('NextSMS is missing its URL or bearer token.', 'nextsms', $this->config->id);
        }
        // Accept either the host or the full endpoint in the URL field
        $url = str_contains($base, '/api/') ? $base : $base . self::PATH;

        try {
            $response = Http::timeout(15)->connectTimeout(5)
                ->withToken($this->config->access_token)
                ->acceptJson()
                ->asJson()
                ->post($url, [
                    'messages'  => [[
                        'from' => $this->config->extra('sender_id'),
                        'to'   => [PhoneNumber::digits($phone) ?? ltrim($phone, '+')],
                        'text' => $message,
                    ]],
                    'flash'     => 0,
                    'reference' => $reference ?? ('CG' . now()->format('ymdHis')),
                ]);
        } catch (Throwable $e) {
            return SmsResult::failed('NextSMS request failed: ' . $e->getMessage(), 'nextsms', $this->config->id);
        }

        $body = $response->json() ?? $response->body();
        $first = is_array($body) ? ($body['messages'][0] ?? null) : null;
        $groupName = strtoupper((string) ($first['status']['groupName'] ?? ''));

        if (!$response->successful() || in_array($groupName, ['REJECTED', 'UNDELIVERABLE'], true)) {
            $error = $first['status']['description']
                ?? (is_array($body) ? ($body['message'] ?? $body['error'] ?? json_encode($body)) : (string) $body);

            return SmsResult::failed('NextSMS HTTP ' . $response->status() . ': ' . mb_strimwidth((string) $error, 0, 300, '…'), 'nextsms', $this->config->id, $response->status(), $body);
        }

        $messageId = $first['messageId'] ?? $first['message_id'] ?? null;

        return new SmsResult(true, 'nextsms', $this->config->id, $messageId ? (string) $messageId : null, null, $response->status(), $body);
    }
}
