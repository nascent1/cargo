<?php

namespace App\Support;

/**
 * Normalises phone numbers for SMS. Local Tanzanian formats (07…, 7…) become +255….
 */
final class PhoneNumber
{
    /**
     * E.164 form (+255712345678), or null when the value can't be a phone number.
     */
    public static function normalize(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $phone = trim($phone);
        if ($phone === '' || preg_match('/[a-z]/i', $phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        } elseif (strlen($digits) === 10 && str_starts_with($digits, '0')) {
            $digits = '255' . substr($digits, 1);
        } elseif (strlen($digits) === 9 && in_array($digits[0], ['6', '7'], true)) {
            $digits = '255' . $digits;
        }

        return strlen($digits) >= 10 && strlen($digits) <= 15 ? '+' . $digits : null;
    }

    /** International digits without the plus (255712345678). */
    public static function digits(?string $phone): ?string
    {
        $e164 = self::normalize($phone);

        return $e164 ? substr($e164, 1) : null;
    }

    /** +255712***678 for logs and screens. */
    public static function mask(?string $phone): string
    {
        $e164 = self::normalize($phone) ?? (string) $phone;

        return strlen($e164) > 7 ? substr($e164, 0, 7) . '***' . substr($e164, -3) : $e164;
    }
}
