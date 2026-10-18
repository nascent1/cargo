<?php

namespace App\Support;

/**
 * Public tracking links (SMS, receipt QR codes): APP_URL from .env + /track/{booking number}.
 *
 * While APP_URL is a loopback address (127.0.0.1, localhost) a customer's phone can't open the
 * link, so callers leave it out rather than send a dead link.
 */
final class TrackingUrl
{
    private const LOCAL_HOSTS = ['localhost', '127.0.0.1', '::1', '[::1]', '0.0.0.0'];

    public static function base(): string
    {
        return rtrim((string) config('app.url'), '/') . '/track';
    }

    public static function for(string $bookingNumber): string
    {
        return self::base() . '/' . rawurlencode($bookingNumber);
    }

    /** Can a customer's phone reach it? False for loopback addresses. */
    public static function isPublic(): bool
    {
        $host = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        return $host !== '' && !in_array($host, self::LOCAL_HOSTS, true) && !str_starts_with($host, '127.');
    }

    /** The link when customers can open it, else null. */
    public static function forCustomer(string $bookingNumber): ?string
    {
        return self::isPublic() ? self::for($bookingNumber) : null;
    }
}
