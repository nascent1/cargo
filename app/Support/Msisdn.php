<?php

namespace App\Support;

/**
 * Tanzanian mobile number helpers: normalisation, network detection, masking.
 */
class Msisdn
{
    /**
     * Number prefixes (after 255 / leading 0) per mobile-money push driver.
     */
    public const NETWORK_PREFIXES = [
        'vodacom_mpesa' => ['74', '75', '76'],
        'airtel_money'  => ['68', '69', '78'],
        'tigo_pesa'     => ['65', '67', '71', '77'],
        'halopesa'      => ['61', '62'],
    ];

    public const NETWORK_LABELS = [
        'vodacom_mpesa' => 'Vodacom',
        'airtel_money'  => 'Airtel',
        'tigo_pesa'     => 'Tigo (Yas)',
        'halopesa'      => 'Halotel',
    ];

    /**
     * Normalise to 255XXXXXXXXX, or null when not a valid Tanzanian mobile number.
     */
    public static function normalize(?string $number): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $number);

        if (str_starts_with($digits, '255') && strlen($digits) === 12) {
            $local = substr($digits, 3);
        } elseif (str_starts_with($digits, '0') && strlen($digits) === 10) {
            $local = substr($digits, 1);
        } elseif (strlen($digits) === 9) {
            $local = $digits;
        } else {
            return null;
        }

        return preg_match('/^[67]\d{8}$/', $local) ? '255' . $local : null;
    }

    /**
     * Push driver whose network owns the number, or null if unknown.
     */
    public static function network(?string $msisdn): ?string
    {
        $normalized = static::normalize($msisdn);
        if (!$normalized) {
            return null;
        }

        $prefix = substr($normalized, 3, 2);
        foreach (static::NETWORK_PREFIXES as $driver => $prefixes) {
            if (in_array($prefix, $prefixes, true)) {
                return $driver;
            }
        }

        return null;
    }

    public static function prefixesFor(?string $driver): array
    {
        return static::NETWORK_PREFIXES[$driver] ?? [];
    }

    /**
     * 0754 *** 456
     */
    public static function mask(?string $msisdn): ?string
    {
        $normalized = static::normalize($msisdn);
        if (!$normalized) {
            return $msisdn;
        }

        $local = '0' . substr($normalized, 3);

        return substr($local, 0, 4) . ' *** ' . substr($local, -3);
    }

    /**
     * 0754123456
     */
    public static function local(?string $msisdn): ?string
    {
        $normalized = static::normalize($msisdn);

        return $normalized ? '0' . substr($normalized, 3) : null;
    }
}
