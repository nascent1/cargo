<?php

namespace App\Gateways\Payment\Push;

/**
 * Normalised outcome of a push initiation, status query or callback.
 */
final class PushResult
{
    public const PENDING = 'pending';
    public const PAID = 'paid';
    public const FAILED = 'failed';

    public function __construct(
        public readonly string $status,
        public readonly ?string $message = null,
        public readonly ?string $providerRequestId = null,
        public readonly ?string $providerReference = null,
        public readonly ?string $ourReference = null,
        public readonly ?float $amount = null,
        public readonly array $raw = [],
    ) {
    }

    public static function pending(?string $message = null, ?string $providerRequestId = null, array $raw = [], ?string $ourReference = null): self
    {
        return new self(self::PENDING, $message, $providerRequestId, null, $ourReference, null, $raw);
    }

    public static function paid(?string $providerReference, ?string $providerRequestId = null, ?float $amount = null, array $raw = [], ?string $ourReference = null): self
    {
        return new self(self::PAID, null, $providerRequestId, $providerReference, $ourReference, $amount, $raw);
    }

    public static function failed(string $message, ?string $providerRequestId = null, array $raw = [], ?string $ourReference = null): self
    {
        return new self(self::FAILED, $message, $providerRequestId, null, $ourReference, null, $raw);
    }

    public function isPaid(): bool
    {
        return $this->status === self::PAID;
    }

    public function isFailed(): bool
    {
        return $this->status === self::FAILED;
    }
}
