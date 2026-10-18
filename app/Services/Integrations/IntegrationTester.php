<?php

namespace App\Services\Integrations;

use App\Models\IntegrationConfig;
use App\Models\NotificationLog;
use App\Models\SystemConfig;
use App\Services\SmsService;
use App\Support\DynamicMailConfig;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

/**
 * "Test" buttons on Settings › Integrations. Works on an unsaved preview, so a maker can
 * check credentials before submitting them. SMS and email tests really send (to the number
 * or address the person types), and the SMS test is written to the delivery log.
 *
 * @phpstan-type Outcome array{ok: bool, message: string, detail: ?string}
 */
class IntegrationTester
{
    public function __construct(private SmsService $sms)
    {
    }

    /** @return array{ok: bool, message: string, detail: ?string} */
    public function test(IntegrationConfig $config, ?string $recipient = null): array
    {
        try {
            return match ($config->channel) {
                'sms'   => $this->sms($config, (string) $recipient),
                'email' => $this->email($config, (string) $recipient),
                default => $this->outcome(false, "No test available for {$config->channel}."),
            };
        } catch (Throwable $e) {
            return $this->outcome(false, 'Test failed: ' . $e->getMessage());
        }
    }

    private function sms(IntegrationConfig $config, string $phone): array
    {
        $to = PhoneNumber::normalize($phone);
        if (!$to) {
            return $this->outcome(false, 'Enter a valid phone number to send the test SMS to.');
        }

        $text = 'Test SMS from ' . $this->platform() . " via {$config->name}. If you received this, the gateway works.";
        $log = NotificationLog::create([
            'user_id' => auth()->id(), 'channel' => 'sms', 'event' => 'integration_test',
            'recipient' => $to, 'content' => $text, 'status' => 'pending', 'provider' => $config->provider,
        ]);

        $result = $this->sms->sendVia($config, $to, $text, 'CG' . $log->id);
        $log->update([
            'status'              => $result->success ? 'sent' : 'failed',
            'provider_message_id' => $result->messageId,
            'error_message'       => $result->error,
            'sent_at'             => $result->success ? now() : null,
        ]);

        return $result->success
            ? $this->outcome(true, "Test SMS sent to " . PhoneNumber::mask($to) . ($result->messageId ? " (message id {$result->messageId})." : '.'), $this->raw($result->raw))
            : $this->outcome(false, $result->error ?? 'The gateway rejected the message.', $this->raw($result->raw));
    }

    private function email(IntegrationConfig $config, string $to): array
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return $this->outcome(false, 'Enter a valid email address to send the test to.');
        }

        $mailer = DynamicMailConfig::registerMailer($config, 'integration_test');
        Mail::mailer($mailer)->raw(
            'This is a test email from ' . $this->platform() . " sent through {$config->name}. If you received it, the SMTP settings work.",
            fn ($m) => $m->to($to)
                ->from($config->extra('from_address'), $config->extra('from_name') ?: $this->platform())
                ->subject('SMTP test — ' . $this->platform())
        );

        return $this->outcome(true, "Test email accepted by {$config->extra('host')} for {$to}. Check the inbox (and spam folder).");
    }

    private function platform(): string
    {
        return SystemConfig::getValue('company_name') ?: (SystemConfig::getValue('platform_name') ?: config('app.name'));
    }

    private function raw(mixed $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        return Str::limit(is_string($raw) ? $raw : json_encode($raw, JSON_UNESCAPED_SLASHES), 500);
    }

    private function outcome(bool $ok, string $message, ?string $detail = null): array
    {
        return ['ok' => $ok, 'message' => $message, 'detail' => $detail];
    }
}
