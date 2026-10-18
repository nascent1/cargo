<?php

namespace App\Services\Sms;

/**
 * Outcome of one SMS send, through one gateway or across the failover chain.
 */
final class SmsResult
{
    /**
     * @param array<int, array{provider: string, config_id: string|null, success: bool, error: string|null}> $attempts
     */
    public function __construct(
        public readonly bool $success,
        public readonly ?string $provider = null,
        public readonly ?string $configId = null,
        public readonly ?string $messageId = null,
        public readonly ?string $error = null,
        public readonly ?int $httpStatus = null,
        public readonly mixed $raw = null,
        public array $attempts = [],
    ) {
    }

    public static function failed(string $error, ?string $provider = null, ?string $configId = null, ?int $httpStatus = null, mixed $raw = null): self
    {
        return new self(false, $provider, $configId, null, $error, $httpStatus, $raw);
    }
}
