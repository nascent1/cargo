<?php

namespace App\Support;

use InvalidArgumentException;

/**
 * One place for rounding, formatting and converting money in the two currencies the business uses.
 *
 * Rules (requirements, section 5.8): TZS amounts are whole shillings, USD amounts have two decimals.
 * An exchange rate is always "TZS for 1 USD", as in the Exchange Rates table.
 *
 * Rounding is half away from zero, the same as CargoFareCalculator, so prices do not move.
 */
final class Money
{
    public const TZS = 'TZS';
    public const USD = 'USD';

    private const DECIMALS = [self::TZS => 0, self::USD => 2];

    /**
     * @return list<string>
     */
    public static function currencies(): array
    {
        return array_keys(self::DECIMALS);
    }

    public static function isSupported(?string $currency): bool
    {
        return $currency !== null && isset(self::DECIMALS[strtoupper(trim($currency))]);
    }

    /** The currency code in its stored form, or an exception for anything but TZS and USD. */
    public static function currency(?string $currency): string
    {
        if (!self::isSupported($currency)) {
            throw new InvalidArgumentException('Unsupported currency "' . $currency . '". Use TZS or USD.');
        }

        return strtoupper(trim($currency));
    }

    public static function decimals(string $currency): int
    {
        return self::DECIMALS[self::currency($currency)];
    }

    /** Rounds to the currency's precision: whole shillings, or cents. */
    public static function round(float|int|string|null $amount, string $currency): float
    {
        return round((float) $amount, self::decimals($currency)) + 0.0; // + 0.0 turns -0.0 into 0.0
    }

    /** "TZS 35,600" or "USD 5,400.00"; without the code when $withCode is false. */
    public static function format(float|int|string|null $amount, string $currency, bool $withCode = true): string
    {
        $currency = self::currency($currency);
        $number = number_format(self::round($amount, $currency), self::DECIMALS[$currency]);

        return $withCode ? $currency . ' ' . $number : $number;
    }

    /**
     * Converts between the two currencies and rounds to the target currency.
     *
     * @param  float|int|string|null  $rate  TZS for 1 USD; not needed when both currencies are the same
     */
    public static function convert(float|int|string|null $amount, string $from, string $to, float|int|string|null $rate = null): float
    {
        $from = self::currency($from);
        $to = self::currency($to);

        if ($from === $to) {
            return self::round($amount, $to);
        }

        $rate = (float) $rate;
        if ($rate <= 0) {
            throw new InvalidArgumentException("An exchange rate above zero is needed to convert {$from} to {$to}.");
        }

        return self::round($from === self::USD ? (float) $amount * $rate : (float) $amount / $rate, $to);
    }

    /** The TZS equivalent of an amount, in whole shillings. */
    public static function toTzs(float|int|string|null $amount, string $currency, float|int|string|null $rate = null): float
    {
        return self::convert($amount, $currency, self::TZS, $rate);
    }

    /**
     * The four values every money record stores together: amount, currency, the rate in force, TZS equivalent.
     * A TZS record keeps the rate for reference when one is given; a USD record must have one.
     *
     * @return array{amount: float, currency: string, exchange_rate: ?float, amount_tzs: float}
     */
    public static function record(float|int|string|null $amount, string $currency, float|int|string|null $rate = null): array
    {
        $currency = self::currency($currency);

        return [
            'amount' => self::round($amount, $currency),
            'currency' => $currency,
            'exchange_rate' => $rate !== null && (float) $rate > 0 ? (float) $rate : null,
            'amount_tzs' => self::toTzs(self::round($amount, $currency), $currency, $rate),
        ];
    }

    /** A percentage of an amount, rounded to the currency. */
    public static function percent(float|int|string|null $amount, float|int|string|null $percent, string $currency): float
    {
        return self::round((float) $amount * (float) $percent / 100, $currency);
    }

    /**
     * Adds amounts of one currency, rounding the total once.
     *
     * @param  iterable<float|int|string|null>  $amounts
     */
    public static function sum(iterable $amounts, string $currency): float
    {
        $total = 0.0;
        foreach ($amounts as $amount) {
            $total += (float) $amount;
        }

        return self::round($total, $currency);
    }

    /** Whether two amounts are the same once rounded to the currency. */
    public static function equals(float|int|string|null $a, float|int|string|null $b, string $currency): bool
    {
        return self::round($a, $currency) === self::round($b, $currency);
    }
}
