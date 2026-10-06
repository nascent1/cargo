<?php

namespace App\Services;

use App\Models\SystemConfig;

/**
 * Centralized exchange rate service.
 *
 * Replaces scattered inline lookups of `usd_to_tzs_exchange_rate` with
 * inconsistent defaults (2600, 2600.00, 2800.0) across services.
 */
class ExchangeRateService
{
    /** The single canonical default when no config exists. */
    private const DEFAULT_USD_TO_TZS = 2600.0;

    /**
     * Get the current exchange rate for a currency pair.
     *
     * Currently supports USD→TZS only. Returns 1.0 for same-currency pairs.
     */
    public static function getCurrentRate(string $from = 'USD', string $to = 'TZS'): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        if ($from === 'USD' && $to === 'TZS') {
            return (float) SystemConfig::getValue('usd_to_tzs_exchange_rate', self::DEFAULT_USD_TO_TZS);
        }

        if ($from === 'TZS' && $to === 'USD') {
            $rate = (float) SystemConfig::getValue('usd_to_tzs_exchange_rate', self::DEFAULT_USD_TO_TZS);
            return $rate > 0 ? round(1.0 / $rate, 6) : 0.0;
        }

        // Unsupported pair — return 1.0 as safe fallback
        return 1.0;
    }

    /**
     * Convert an amount from one currency to another using the current rate.
     */
    public static function convert(float $amount, string $from, string $to): float
    {
        $rate = self::getCurrentRate($from, $to);
        return round($amount * $rate, 2);
    }

    /**
     * Get the rate to convert any supported currency to TZS.
     * Returns 1.0 if the currency is already TZS.
     */
    public static function rateToTzs(string $currency): float
    {
        return self::getCurrentRate($currency, 'TZS');
    }

    /**
     * Convert an amount to TZS.
     */
    public static function toTzs(float $amount, string $currency): float
    {
        return self::convert($amount, $currency, 'TZS');
    }
}
