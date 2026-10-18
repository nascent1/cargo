<?php

namespace App\Services\Sms;

use App\Models\IntegrationConfig;

/**
 * One SMS provider's API. Built per integration_configs row; SmsService handles failover.
 */
interface SmsDriver
{
    public function __construct(IntegrationConfig $config);

    /**
     * @param string      $phone     E.164 (+255…); drivers reformat as their API needs
     * @param string|null $reference our own id for the message (e.g. notification log id)
     */
    public function send(string $phone, string $message, ?string $reference = null): SmsResult;
}
