<?php

namespace App\Support;

use App\Models\IntegrationConfig;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Points Laravel's mailer at the SMTP servers configured in Settings › Integrations.
 *
 * Each active server becomes a mailer ("integration_1", "integration_2", …); with more than one
 * they are chained through Laravel's failover transport in priority order. The primary's From
 * address is used for all of them. Without any active server, the .env mail settings stay in effect.
 *
 * Runs when the mail manager is first resolved, so long-running queue workers pick up
 * changes only after a restart.
 */
final class DynamicMailConfig
{
    public static function apply(): void
    {
        try {
            $servers = IntegrationConfig::prioritised('email')
                ->filter(fn ($c) => IntegrationCatalog::isComplete($c))
                ->values();
        } catch (Throwable $e) {
            return; // table missing during install/migrations
        }

        if ($servers->isEmpty()) {
            return;
        }

        $names = [];
        foreach ($servers as $i => $server) {
            $names[] = self::registerMailer($server, 'integration_' . ($i + 1));
        }

        if (count($names) > 1) {
            config(['mail.mailers.integration_failover' => ['transport' => 'failover', 'mailers' => $names]]);
            config(['mail.default' => 'integration_failover']);
        } else {
            config(['mail.default' => $names[0]]);
        }

        $primary = $servers->first();
        config(['mail.from' => [
            'address' => $primary->extra('from_address'),
            'name'    => $primary->extra('from_name') ?: config('app.name'),
        ]]);
    }

    /**
     * True when email is really delivered: an active SMTP integration, or a non-log mailer in .env.
     * Notifications that carry passwords skip email otherwise (a "log" mailer writes them to the log file).
     */
    public static function delivers(): bool
    {
        try {
            if (IntegrationConfig::prioritised('email')->contains(fn ($c) => IntegrationCatalog::isComplete($c))) {
                return true;
            }
        } catch (Throwable) {
            // table missing during install
        }

        return !in_array(config('mail.default'), ['log', 'array', null], true);
    }

    /**
     * Register one SMTP server as a named mailer (also used by the Test button).
     */
    public static function registerMailer(IntegrationConfig $server, string $name): string
    {
        $encryption = $server->extra('encryption', 'tls');

        config(["mail.mailers.{$name}" => [
            'transport' => 'smtp',
            'scheme'    => $encryption === 'ssl' ? 'smtps' : 'smtp',
            'host'      => $server->extra('host'),
            'port'      => (int) $server->extra('port', $encryption === 'ssl' ? 465 : 587),
            'username'  => $server->extra('username'),
            'password'  => $server->api_secret,
            'timeout'   => 15,
        ]]);

        try {
            app('mail.manager')->purge($name);
        } catch (Throwable $e) {
            Log::debug('Mailer purge skipped: ' . $e->getMessage());
        }

        return $name;
    }
}
