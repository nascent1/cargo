<?php

namespace App\Services;

use App\Models\Consignment;
use App\Models\NotificationLog;
use App\Models\NotificationTemplate;
use App\Models\SystemConfig;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * The cargo SMS policy: which events send an SMS, with which template, to whom.
 *
 * Text comes from Notifications › Templates (channel "sms", default_locale, falling back to en);
 * switching a template off stops that SMS. Customer and summary messages ("gated") also need the
 * sms_enabled master switch; account and password messages always go out. Every attempt — sent,
 * failed or skipped — is written to notification_logs (login details are never stored there).
 */
class SmsNotifier
{
    public const EVENTS = [
        'cargo_booking_sender'    => ['label' => 'Booking details — sender',   'recipient' => 'Sender, when the booking is paid / confirmed',   'gated' => true, 'switch' => 'sms_booking_confirmation_sender'],
        'cargo_booking_receiver'  => ['label' => 'Booking details — receiver', 'recipient' => 'Receiver, when the booking is paid / confirmed', 'gated' => true, 'switch' => 'sms_booking_confirmation_receiver'],
        'cargo_cancelled'         => ['label' => 'Booking cancelled',          'recipient' => 'Sender (paid bookings only)',                   'gated' => true],
        'cargo_in_transit'        => ['label' => 'Cargo on the way',           'recipient' => 'Sender and receiver, when the vessel departs with it', 'gated' => true, 'switch' => 'sms_cargo_in_transit'],
        'cargo_arrived'           => ['label' => 'Cargo arrived',              'recipient' => 'Sender and receiver, when the vessel arrives',  'gated' => true, 'switch' => 'sms_cargo_arrived'],
        'cargo_collection_otp'    => ['label' => 'Collection code',            'recipient' => 'The mobile given by the person collecting',     'gated' => false, 'sensitive' => 'a one-time code'],
        'close_of_business'       => ['label' => 'Close of business summary',  'recipient' => 'Users with “Receive close-of-business SMS”',     'gated' => true],
        'dual_control_request'    => ['label' => 'Approval needed',            'recipient' => 'Users who can approve (not the maker); at most one SMS per 10 minutes each, later ones bundled', 'gated' => true],
        'account_credentials'     => ['label' => 'New account login details',  'recipient' => 'The new user (staff, agent, agent staff)',      'gated' => false, 'sensitive' => true],
        'password_reset_link'     => ['label' => 'Password reset link',        'recipient' => 'The user who asked',                            'gated' => false, 'sensitive' => true],
        'password_reset_by_admin' => ['label' => 'Password reset by an admin', 'recipient' => 'The user',                                      'gated' => false, 'sensitive' => true],
    ];

    /** Approval SMS: at most one per checker in this many minutes; later requests are bundled */
    public const APPROVAL_SMS_WINDOW_MINUTES = 10;

    public function __construct(private SmsService $sms)
    {
    }

    /**
     * Render and send an event's SMS. Never throws — a notification must not break the business flow.
     *
     * @param array<string, scalar|null> $data template placeholders
     */
    public function send(string $event, ?string $phone, array $data = [], ?int $userId = null): NotificationLog
    {
        $policy = self::EVENTS[$event] ?? null;
        $to = PhoneNumber::normalize($phone);

        $log = new NotificationLog([
            'user_id' => $userId,
            'channel' => 'sms',
            'event' => $event,
            'recipient' => $to ?? (string) ($phone ?: '—'),
            'status' => 'pending',
        ]);

        try {
            $skip = match (true) {
                $policy === null => 'Not an SMS event under the SMS policy.',
                $policy['gated'] && !self::masterSwitchOn() => 'SMS notifications are switched off (sms_enabled).',
                isset($policy['switch']) && !self::isOn($policy['switch']) => "Switched off in General settings ({$policy['switch']}).",
                $to === null => $phone ? "Not a valid phone number: {$phone}" : 'No phone number on record.',
                default => null,
            };

            $text = $skip ? null : $this->render($event, $data);
            if (!$skip && $text === null) {
                $skip = 'The SMS template for this event is switched off or missing (Notifications › Templates).';
            }

            if ($skip) {
                $log->fill(['status' => 'skipped', 'error_message' => $skip])->save();

                return $log;
            }

            $sensitive = $policy['sensitive'] ?? false;
            $log->content = $sensitive ? '[hidden — contains ' . (is_string($sensitive) ? $sensitive : 'login details') . ']' : $text;
            $log->save();

            $result = $this->sms->send($to, $text, 'CG' . $log->id);
            $failovers = collect($result->attempts)->reject(fn ($a) => $a['success'])->map(fn ($a) => "{$a['provider']}: {$a['error']}");

            $log->update([
                'status' => $result->success ? 'sent' : 'failed',
                'provider' => $result->provider,
                'provider_message_id' => $result->messageId,
                'error_message' => $result->success
                    ? ($failovers->isNotEmpty() ? 'Delivered after failover — ' . $failovers->implode(' | ') : null)
                    : ($failovers->implode(' | ') ?: $result->error),
                'sent_at' => $result->success ? now() : null,
            ]);
        } catch (Throwable $e) {
            Log::warning("SMS for {$event} failed: {$e->getMessage()}");
            try {
                $log->fill(['status' => 'failed', 'error_message' => mb_strimwidth($e->getMessage(), 0, 1000)])->save();
            } catch (Throwable) {
                // logging must never break the caller either
            }
        }

        return $log;
    }

    /**
     * Same as send(), but only once the surrounding database transaction commits (immediately when
     * there is none), so a rolled-back change never texts anyone.
     */
    public function sendAfterCommit(string $event, ?string $phone, array $data = [], ?int $userId = null): void
    {
        DB::afterCommit(fn () => $this->send($event, $phone, $data, $userId));
    }

    // ── Cargo events ─────────────────────────────────────────

    /**
     * Booking details to the sender and the receiver (when the booking is paid / confirmed).
     */
    public function bookingConfirmed(Consignment $consignment): void
    {
        $data = $this->bookingData($consignment);
        $this->send('cargo_booking_sender', $consignment->sender_phone, $data);
        $this->send('cargo_booking_receiver', $consignment->receiver_phone, $data);
    }

    public function bookingCancelled(Consignment $consignment, ?string $reason = null): void
    {
        $this->send('cargo_cancelled', $consignment->sender_phone, $this->bookingData($consignment) + ['reason' => $reason ?: '—']);
    }

    /**
     * Approval-needed SMS to one checker. The first one goes out; within the next window later
     * requests are logged as bundled and announced together by sendBundledApprovals().
     */
    public function approvalNeeded(User $checker, \App\Models\DualControlRequest $request): NotificationLog
    {
        $recent = NotificationLog::where('event', 'dual_control_request')->where('user_id', $checker->id)
            ->where('status', 'sent')->where('sent_at', '>=', now()->subMinutes(self::APPROVAL_SMS_WINDOW_MINUTES))->exists();

        if ($recent) {
            return NotificationLog::create([
                'user_id' => $checker->id, 'channel' => 'sms', 'event' => 'dual_control_request',
                'recipient' => PhoneNumber::normalize($checker->phone) ?? (string) ($checker->phone ?: '—'),
                'status' => 'skipped', 'error_message' => 'Bundled: #DC-' . $request->id . ' is included in the next approval SMS.',
            ]);
        }

        return $this->send('dual_control_request', $checker->phone, $this->approvalData($request, $checker), $checker->id);
    }

    /**
     * Scheduler: checkers with bundled approval requests whose window has passed get one SMS with the count.
     */
    public function sendBundledApprovals(): int
    {
        $sent = 0;
        $bundled = NotificationLog::where('event', 'dual_control_request')->where('status', 'skipped')
            ->where('error_message', 'like', 'Bundled:%')->where('created_at', '>=', now()->subDay())
            ->select('user_id')->distinct()->pluck('user_id');

        foreach ($bundled as $userId) {
            $last = NotificationLog::where('event', 'dual_control_request')->where('user_id', $userId)->where('status', 'sent')->latest('id')->first();
            if ($last?->sent_at && now()->diffInMinutes($last->sent_at, true) < self::APPROVAL_SMS_WINDOW_MINUTES) {
                continue;
            }
            // Bundled after that SMS (by id: timestamps are whole seconds, a burst shares one)
            $waiting = NotificationLog::where('event', 'dual_control_request')->where('user_id', $userId)->where('status', 'skipped')
                ->where('error_message', 'like', 'Bundled:%')->when($last, fn ($q) => $q->where('id', '>', $last->id))->exists();
            $checker = User::find($userId);
            $latest = \App\Models\DualControlRequest::with('maker')->where('status', 'pending')->where('maker_id', '!=', $userId)
                ->where('expires_at', '>', now())->latest('id')->first();
            if (!$waiting || !$checker || !$latest) {
                continue;
            }
            $this->send('dual_control_request', $checker->phone, $this->approvalData($latest, $checker), $checker->id);
            $sent++;
        }

        return $sent;
    }

    // ── Rendering ────────────────────────────────────────────

    /**
     * Template text for an event, or null when its SMS template is switched off / missing.
     */
    public function render(string $event, array $data): ?string
    {
        $locale = (string) SystemConfig::getValue('default_locale', 'en');
        $template = NotificationTemplate::findTemplate($event, 'sms', $locale);
        if (!$template) {
            return null;
        }

        $data += ['company_name' => self::companyName()];
        $text = $template->render(array_map(fn ($v) => (string) ($v ?? ''), $data));
        $text = preg_replace('/\{\{\s*\w+\s*\}\}/', '', $text);

        return trim(preg_replace('/[ \t]{2,}/', ' ', $text));
    }

    /**
     * In transit / arrived: the same text to the sender and the receiver (once if they share a number).
     */
    public function cargoStatus(Consignment $consignment, string $event): void
    {
        $data = $this->bookingData($consignment);
        $sent = [];
        foreach ([$consignment->sender_phone, $consignment->receiver_phone] as $phone) {
            $key = PhoneNumber::normalize($phone) ?? (string) $phone;
            if (isset($sent[$key])) {
                continue;
            }
            $sent[$key] = true;
            $this->send($event, $phone, $data);
        }
    }

    public function bookingData(Consignment $consignment): array
    {
        $consignment->loadMissing(['route.origin', 'route.destination', 'transportService.transportUnit', 'items.cargoType']);
        $service = $consignment->transportService;
        // APP_URL + /track; left out while that is a local address a phone can't open
        $trackingUrl = \App\Support\TrackingUrl::forCustomer($consignment->consignment_number);
        $trackWord = SystemConfig::getValue('default_locale', 'en') === 'sw' ? 'Fuatilia' : 'Track';
        $items = $consignment->items->where('status', '!=', 'cancelled');

        return [
            'booking_reference' => $consignment->consignment_number,
            'sender_name' => $consignment->sender_name,
            'receiver_name' => $consignment->receiver_name,
            'route' => $consignment->route?->name,
            'origin' => $consignment->route?->origin?->name,
            'destination' => $consignment->route?->destination?->name,
            'departure' => $service ? \Carbon\Carbon::parse($service->departure_date)->format('d M') . ' ' . substr((string) $service->departure_time, 0, 5) : '',
            'vessel' => $service?->transportUnit?->name,
            'item_count' => (int) $items->sum('quantity') ?: $items->count(),
            'items' => $items->map(fn ($i) => $i->quantity . ' ' . ($i->cargoType?->name ?? 'item'))->take(3)->implode(', '),
            'currency' => $consignment->currency ?? 'TZS',
            'amount' => number_format((float) $consignment->net_amount),
            'tracking_url' => $trackingUrl ?? '',
            'tracking_line' => $trackingUrl ? " {$trackWord}: {$trackingUrl}" : '',
        ];
    }

    private function approvalData(\App\Models\DualControlRequest $request, User $checker): array
    {
        $count = \App\Models\DualControlRequest::where('status', 'pending')->where('maker_id', '!=', $checker->id)->where('expires_at', '>', now())->count();

        return [
            'count' => max(1, $count),
            'label' => $request->label(),
            'request_id' => $request->id,
            'maker' => $request->maker?->full_name ?? 'a user',
            'takes_effect' => $request->commence_at ? 'from ' . $request->commence_at->format('d M H:i') : 'when approved',
            'url' => route('admin.dual-control.index'),
        ];
    }

    public static function masterSwitchOn(): bool
    {
        return self::isOn('sms_enabled');
    }

    private static function isOn(string $key): bool
    {
        return in_array(strtolower((string) SystemConfig::getValue($key, '0')), ['1', 'true', 'yes', 'on'], true);
    }

    public static function companyName(): string
    {
        return SystemConfig::getValue('company_name') ?: (SystemConfig::getValue('platform_name') ?: config('app.name'));
    }

    /**
     * Data for the SMS policy panel on Settings › Integrations.
     */
    public function policySummary(): array
    {
        $templates = NotificationTemplate::where('channel', 'sms')->get()->groupBy('event');
        $today = NotificationLog::where('channel', 'sms')->whereDate('created_at', today())
            ->selectRaw('status, count(*) as n')->groupBy('status')->pluck('n', 'status');

        return [
            'enabled' => self::masterSwitchOn(),
            'gateway_ready' => SmsService::gatewayReady(),
            'events' => collect(self::EVENTS)->map(fn ($p, $event) => $p + [
                'key' => $event,
                'switch_on' => !isset($p['switch']) || self::isOn($p['switch']),
                'template_on' => ($templates[$event] ?? collect())->contains('is_active', true),
                'locales' => ($templates[$event] ?? collect())->where('is_active', true)->pluck('locale')->unique()->values()->all(),
            ])->values(),
            'today' => ['sent' => (int) ($today['sent'] ?? 0), 'failed' => (int) ($today['failed'] ?? 0), 'skipped' => (int) ($today['skipped'] ?? 0)],
            'tracking' => ['url' => \App\Support\TrackingUrl::base(), 'public' => \App\Support\TrackingUrl::isPublic()],
        ];
    }
}
