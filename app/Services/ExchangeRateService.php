<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\SystemConfig;
use Carbon\Carbon;
use Carbon\CarbonInterface;

/**
 * Centralized exchange rate service.
 *
 * A rate comes from Exchange Rates: the entry for the pair whose window covers the day (the latest start wins),
 * so a rate can be set ahead of time and history stays as it was. Any currency with an X→TZS entry converts,
 * other pairs go through TZS. USD→TZS falls back to the `usd_to_tzs_exchange_rate` setting, then 2600.
 */
class ExchangeRateService
{
    /** The single canonical default when no config exists. */
    private const DEFAULT_USD_TO_TZS = 2600.0;

    /**
     * Get the rate for a currency pair on a day (default today).
     *
     * Returns 1.0 for same-currency pairs, and for a currency with no rate (safe fallback).
     */
    public static function getCurrentRate(string $from = 'USD', string $to = 'TZS', ?CarbonInterface $on = null): float
    {
        $from = strtoupper($from);
        $to = strtoupper($to);

        if ($from === $to) {
            return 1.0;
        }

        $fromTzs = self::toTzsRate($from, $on);
        $toTzs = self::toTzsRate($to, $on);
        if (!$fromTzs || !$toTzs) {
            return 1.0;
        }

        return $to === 'TZS' ? $fromTzs : round($fromTzs / $toTzs, 6);
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

    /**
     * The Exchange Rates entry in force for CURRENCY→TZS on a day, if any.
     */
    public static function inForce(string $currency, ?CarbonInterface $on = null): ?ExchangeRate
    {
        $day = ($on ?? today())->toDateString();

        return ExchangeRate::where('from_currency', strtoupper($currency))->where('to_currency', 'TZS')
            ->whereDate('effective_from', '<=', $day)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $day))
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->first();
    }

    /**
     * Whether the USD→TZS rate is due for its periodic update (exchange_rate_update_frequency days; 0 = never).
     *
     * @return array{rate: float, since: ?string, frequency_days: int, last_updated: ?string, due_on: ?string, due: bool}
     */
    public static function updateStatus(): array
    {
        $frequency = max(0, (int) SystemConfig::getValue('exchange_rate_update_frequency', 1));
        try {
            $last = Carbon::parse((string) SystemConfig::getValue('exchange_rate_last_updated', '2000-01-01'))->startOfDay();
        } catch (\Exception) {
            $last = null; // unreadable: treat as never set
        }
        $dueOn = $frequency > 0 ? ($last ? $last->copy()->addDays($frequency) : today()) : null;

        return [
            'rate' => self::getCurrentRate('USD', 'TZS'),
            'since' => self::inForce('USD')?->effective_from?->toDateString(),
            'frequency_days' => $frequency,
            'last_updated' => $last?->toDateString(),
            'due_on' => $dueOn?->toDateString(),
            'due' => $dueOn !== null && $dueOn->lte(today()),
        ];
    }

    /**
     * A USD→TZS rate was set: the periodic update is done, even while the rate waits for a checker.
     */
    public static function markUpdated(): void
    {
        SystemConfig::setValue('exchange_rate_last_updated', today()->toDateString());
    }

    private static function toTzsRate(string $currency, ?CarbonInterface $on): ?float
    {
        if ($currency === 'TZS') {
            return 1.0;
        }

        $rate = (float) self::inForce($currency, $on)?->rate;
        if ($rate > 0) {
            return $rate;
        }

        return $currency === 'USD' ? (float) SystemConfig::getValue('usd_to_tzs_exchange_rate', self::DEFAULT_USD_TO_TZS) : null;
    }
}
