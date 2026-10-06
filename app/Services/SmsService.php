<?php

namespace App\Services;

use App\Models\IntegrationConfig;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    protected string $apiKey;
    protected string $apiSecret;
    protected string $senderId;
    protected string $baseUrl;

    public function __construct()
    {
        $this->loadConfig();
    }

    protected function loadConfig()
    {
        $integration = IntegrationConfig::where('channel', 'sms')
            ->where('is_active', 1)
            ->first();

        $this->apiKey = $integration?->api_key ?? '';
        $this->apiSecret = $integration?->api_secret ?? '';
        
        $extra = $integration?->extra_credentials ?? [];
        $this->senderId = $extra['sender_id'] ?? 'Sokomet';

        // Load URL from configuration (fallback to base_url if production_url is missing)
        $this->baseUrl = $integration?->production_url ?? $integration?->base_url ?? 'https://messaging.kilakona.co.tz/api/v1/vendor/message/send';
    }

    public function sendSms(string $phone, string $message): bool
    {
        if (empty($this->apiKey) || empty($this->apiSecret)) {
            Log::info('Kilakona SMS config missing. Simulated SMS to ' . $phone . ': ' . $message);
            return true;
        }

        try {
            $postData = [
                'senderId' => $this->senderId,
                'messageType' => 'text',
                'message' => $message,
                'contacts' => $phone
            ];

            // In local/dev, always log the outgoing SMS so developers can see auto-generated passwords
            Log::info('Outgoing SMS', ['phone' => $phone, 'message' => $message]);

            $response = Http::withoutVerifying()->withHeaders([
                'Content-Type' => 'application/json',
                'api_key' => $this->apiKey,
                'api_secret' => $this->apiSecret,
            ])->post($this->baseUrl, $postData);

            if ($response->successful()) {
                return true;
            }

            Log::error('Kilakona SMS failed', ['response' => $response->body()]);
            return false;

        } catch (\Exception $e) {
            Log::error('Kilakona SMS exception', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
