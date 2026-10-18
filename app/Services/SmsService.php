<?php

namespace App\Services;

use App\Models\IntegrationConfig;
use App\Services\Sms\SmsDriver;
use App\Services\Sms\SmsResult;
use App\Support\IntegrationCatalog;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends SMS through the gateways configured in Settings › Integrations, in priority order:
 * when the primary fails the next active gateway is tried.
 *
 * Business messages go through SmsNotifier (templates, SMS policy, delivery log). This class only delivers.
 */
class SmsService
{
    /**
     * Send through the failover chain.
     */
    public function send(string $phone, string $message, ?string $reference = null): SmsResult
    {
        $to = PhoneNumber::normalize($phone);
        if (!$to) {
            return SmsResult::failed("Not a valid phone number: {$phone}");
        }

        $gateways = IntegrationConfig::prioritised('sms')->filter(fn ($c) => IntegrationCatalog::isComplete($c));
        if ($gateways->isEmpty()) {
            Log::warning('SMS not sent: no active SMS gateway is configured.', ['to' => PhoneNumber::mask($to)]);

            return SmsResult::failed('No active SMS gateway is configured (Settings › Integrations).');
        }

        if (app()->environment('local')) {
            Log::info('Outgoing SMS', ['phone' => PhoneNumber::mask($to), 'length' => mb_strlen($message)]);
        }

        $attempts = [];
        $result = null;
        foreach ($gateways as $config) {
            $result = $this->sendVia($config, $to, $message, $reference);
            $attempts[] = ['provider' => $config->provider, 'config_id' => $config->id, 'success' => $result->success, 'error' => $result->error];

            if ($result->success) {
                break;
            }
            Log::warning("SMS via {$config->name} failed, trying the next gateway", ['to' => PhoneNumber::mask($to), 'error' => $result->error]);
        }

        $result->attempts = $attempts;

        return $result;
    }

    /**
     * Send through one specific gateway (the failover loop and the Test button).
     */
    public function sendVia(IntegrationConfig $config, string $phone, string $message, ?string $reference = null): SmsResult
    {
        $to = PhoneNumber::normalize($phone);
        if (!$to) {
            return SmsResult::failed("Not a valid phone number: {$phone}", $config->provider, $config->id);
        }

        try {
            return static::driverFor($config)->send($to, $message, $reference);
        } catch (Throwable $e) {
            return SmsResult::failed($e->getMessage(), $config->provider, $config->id);
        }
    }

    /**
     * Boolean API kept for existing callers (Lipa Namba instructions, legacy flows).
     */
    public function sendSms(string $phone, string $message): bool
    {
        return $this->send($phone, $message)->success;
    }

    public static function driverFor(IntegrationConfig $config): SmsDriver
    {
        $class = IntegrationCatalog::provider($config->provider)['driver'] ?? null;
        if (!$class) {
            throw new \InvalidArgumentException("No SMS driver for provider [{$config->provider}].");
        }

        return new $class($config);
    }

    /**
     * True when at least one complete SMS gateway is active.
     */
    public static function gatewayReady(): bool
    {
        return IntegrationConfig::prioritised('sms')->contains(fn ($c) => IntegrationCatalog::isComplete($c));
    }
}
