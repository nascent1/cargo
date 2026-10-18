<?php

namespace App\Services\Cargo;

use App\Models\BusinessDayClosure;
use App\Models\CargoPayment;
use App\Models\Consignment;
use App\Models\NotificationLog;
use App\Models\Shift;
use App\Models\SystemConfig;
use App\Models\TransportService;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Services\SmsService;
use App\Support\PhoneNumber;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Close of business: once today's last (non-cancelled) cargo departure has left plus a buffer —
 * or at the fallback time on days without departures — the day is closed and a summary SMS goes
 * to every active user holding "Receive close-of-business SMS" (close_of_business:receive).
 *
 * Each closure covers the period since the previous one, so a payment taken after tonight's
 * closure appears in tomorrow's summary: nothing is missed or counted twice.
 */
class CloseOfBusinessService
{
    public const PERMISSION = 'close_of_business:receive';

    public function __construct(private SmsNotifier $notifier, private SmsService $sms)
    {
    }

    /**
     * When today closes: last departure + buffer, else the fallback time.
     *
     * @return array{at: CarbonImmutable, trigger: string, service: ?TransportService}
     */
    public function cutoff(?CarbonImmutable $day = null): array
    {
        $day = ($day ?? CarbonImmutable::now())->startOfDay();
        $service = TransportService::whereDate('departure_date', $day->toDateString())
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('departure_time')
            ->first();

        if ($service) {
            $buffer = (int) SystemConfig::getValue('close_of_business_buffer_minutes', 30);

            return [
                'at'      => $day->setTimeFromTimeString((string) $service->departure_time)->addMinutes($buffer),
                'trigger' => 'last_departure',
                'service' => $service,
            ];
        }

        $fallback = (string) SystemConfig::getValue('close_of_business_fallback_time', '20:00');
        if (!preg_match('/^\d{1,2}:\d{2}$/', $fallback)) {
            $fallback = '20:00';
        }

        return ['at' => $day->setTimeFromTimeString($fallback), 'trigger' => 'fallback', 'service' => null];
    }

    /**
     * Close today if it's due (or $force), and send the summary. Null when not due / already closed.
     */
    public function closeIfDue(bool $force = false, ?int $closedBy = null): ?BusinessDayClosure
    {
        $now = CarbonImmutable::now();
        $cutoff = $this->cutoff($now);

        if (BusinessDayClosure::whereDate('business_date', $now->toDateString())->exists()) {
            return null;
        }
        if (!$force && $now->lt($cutoff['at'])) {
            return null;
        }

        $closure = DB::transaction(function () use ($now, $cutoff, $force, $closedBy) {
            // unique business_date guards against two schedulers racing
            $start = $this->periodStart($now);

            return BusinessDayClosure::create([
                'business_date'   => $now->toDateString(),
                'period_start'    => $start,
                'period_end'      => $now,
                'trigger'         => $force ? 'manual' : $cutoff['trigger'],
                'last_service_id' => $cutoff['service']?->id,
                'summary'         => $this->summary($start, $now),
                'closed_by'       => $closedBy,
            ]);
        });

        return $this->deliver($closure);
    }

    /**
     * Send (or resend to those who didn't get it) the closure's SMS.
     */
    public function deliver(BusinessDayClosure $closure): BusinessDayClosure
    {
        $deliveries = $closure->deliveries ?? [];
        $data = $this->templateData($closure);
        $recipients = $this->recipients();

        foreach ($recipients as $user) {
            if (($deliveries[$user->id] ?? null) === 'sent') {
                continue;
            }
            $log = $this->notifier->send('close_of_business', $user->phone, $data, $user->id);
            $deliveries[$user->id] = $log->status;
        }

        $closure->update([
            'deliveries'       => $deliveries,
            'recipients_count' => $recipients->count(),
            'sent_count'       => collect($deliveries)->filter(fn ($s) => $s === 'sent')->count(),
            'failed_count'     => collect($deliveries)->reject(fn ($s) => $s === 'sent')->count(),
        ]);

        return $closure;
    }

    /**
     * Active staff with "Receive close-of-business SMS" (role or direct grant; super_admin
     * always) and a usable phone.
     */
    public function recipients(): Collection
    {
        return $this->holders()->filter(fn (User $u) => PhoneNumber::normalize($u->phone) !== null)->values();
    }

    /**
     * Users with the permission but no usable phone (shown as a warning on the panel).
     */
    public function recipientsWithoutPhone(): Collection
    {
        return $this->holders()->filter(fn (User $u) => PhoneNumber::normalize($u->phone) === null)->values();
    }

    /**
     * Figures for a period. Money is what was actually collected (paid cargo payments confirmed
     * in the period), so a booking made yesterday and paid today counts today.
     */
    public function summary(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $primary = (string) SystemConfig::getValue('platform_currency', 'TZS');

        $byChannel = CargoPayment::query()
            ->join('payment_methods', 'payment_methods.id', '=', 'cargo_payments.payment_method_id')
            ->where('cargo_payments.status', 'paid')
            ->where('cargo_payments.confirmed_at', '>', $from)->where('cargo_payments.confirmed_at', '<=', $to)
            ->selectRaw('payment_methods.name as method, cargo_payments.currency, SUM(cargo_payments.amount) as total, COUNT(*) as n')
            ->groupBy('payment_methods.name', 'cargo_payments.currency')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => ['method' => (string) $r->method, 'currency' => (string) $r->currency, 'total' => (float) $r->total, 'count' => (int) $r->n])
            ->all();

        // Primary currency first, then any others (e.g. USD-priced cargo)
        $revenue = collect($byChannel)->groupBy('currency')->map(fn ($rows) => (float) $rows->sum('total'))
            ->sortKeysUsing(fn ($a, $b) => ($b === $primary) <=> ($a === $primary) ?: strcmp($a, $b))
            ->all();

        $paid = Consignment::where('paid_at', '>', $from)->where('paid_at', '<=', $to)->where('payment_status', 'paid');

        return [
            'currency'        => $primary,
            'revenue'         => $revenue,
            'by_channel'      => $byChannel,
            'booking_count'   => (clone $paid)->count(),
            'weight_kg'       => (float) (clone $paid)->sum('total_weight_kg'),
            // Made in the period and still waiting for money
            'unpaid_count'    => Consignment::where('created_at', '>', $from)->where('created_at', '<=', $to)
                ->where('payment_status', 'unpaid')->where('status', '!=', 'cancelled')->count(),
            'cancelled_count' => Consignment::where('cancelled_at', '>', $from)->where('cancelled_at', '<=', $to)->count(),
            // Outstanding, not just this period: money received for a paid/cancelled booking
            'refunds_due'     => CargoPayment::where('status', 'refund_due')->count(),
            'shifts_worked'   => Shift::where('start_time', '>', $from)->where('start_time', '<=', $to)->distinct()->count('user_id'),
            // Time ran out but not yet closed (clerk idle) doesn't count as open
            'shifts_open'     => Shift::active()->where(fn ($q) => $q->whereNull('end_time')->orWhere('end_time', '>', $to))->count(),
        ];
    }

    /** Placeholders for the close_of_business template. */
    public function templateData(BusinessDayClosure $closure): array
    {
        $s = $closure->summary;
        $start = CarbonImmutable::parse($closure->period_start);
        $end = CarbonImmutable::parse($closure->period_end);
        $primary = $s['currency'] ?? 'TZS';
        $money = fn ($v) => number_format((float) $v, 0);
        $sw = SystemConfig::getValue('default_locale', 'en') === 'sw';

        $revenue = collect($s['revenue'] ?? []);
        $revenueText = $money($revenue->get($primary, 0));
        foreach ($revenue->except($primary) as $currency => $total) {
            $revenueText .= " + {$currency} {$money($total)}";
        }

        $channels = collect($s['by_channel'] ?? [])->take(4)
            ->map(fn ($c) => $c['method'] . ' ' . ($c['currency'] === $primary ? '' : $c['currency'] . ' ') . $money($c['total']))
            ->implode(' · ');

        $worked = (int) ($s['shifts_worked'] ?? 0);
        $open = (int) ($s['shifts_open'] ?? 0);
        $shiftLine = '';
        if ($worked || $open) {
            $shiftLine = $sw
                ? " Zamu: wafanyakazi {$worked}" . ($open ? ", {$open} bado wazi." : '.')
                : " Shifts: {$worked} staff" . ($open ? ", {$open} still open." : '.');
        }

        return [
            'date'              => $end->format('d M Y'),
            'period'            => $start->isSameDay($end) ? $start->format('H:i') . '–' . $end->format('H:i') : ($sw ? 'tangu ' : 'since ') . $start->format('d M H:i'),
            'booking_count'     => (int) ($s['booking_count'] ?? 0),
            'currency'          => $primary,
            'revenue'           => $revenueText,
            'channel_breakdown' => $channels ?: ($sw ? 'hakuna mauzo' : 'no sales'),
            'weight_kg'         => number_format((float) ($s['weight_kg'] ?? 0), 0),
            'unpaid_count'      => (int) ($s['unpaid_count'] ?? 0),
            'cancelled_count'   => (int) ($s['cancelled_count'] ?? 0),
            'refunds_due'       => (int) ($s['refunds_due'] ?? 0),
            'shift_line'        => $shiftLine,
        ];
    }

    /**
     * What tonight's SMS would say if the day closed now (not saved).
     */
    public function preview(): array
    {
        $now = CarbonImmutable::now();
        $draft = new BusinessDayClosure([
            'business_date' => $now->toDateString(),
            'period_start'  => $this->periodStart($now),
            'period_end'    => $now,
        ]);
        $draft->summary = $this->summary(CarbonImmutable::parse($draft->period_start), $now);

        return [
            'text'       => $this->notifier->render('close_of_business', $this->templateData($draft)),
            'recipients' => $this->recipients()->map(fn (User $u) => $u->full_name . ' (' . PhoneNumber::mask($u->phone) . ')')->all(),
        ];
    }

    /**
     * Send the current preview to one user — a test that ignores the sms_enabled switch.
     */
    public function sendPreviewTo(User $user): NotificationLog
    {
        $text = $this->preview()['text'] ?? null;
        $to = PhoneNumber::normalize($user->phone);

        $log = NotificationLog::create([
            'user_id' => $user->id, 'channel' => 'sms', 'event' => 'close_of_business_test',
            'recipient' => $to ?? (string) ($user->phone ?: '—'), 'content' => $text, 'status' => 'pending',
        ]);

        if (!$text || !$to) {
            $log->update(['status' => 'skipped', 'error_message' => !$text ? 'The close_of_business SMS template is switched off.' : 'You have no valid phone number on your profile.']);

            return $log;
        }

        $result = $this->sms->send($to, '[TEST] ' . $text, 'CG' . $log->id);
        $log->update([
            'status' => $result->success ? 'sent' : 'failed', 'provider' => $result->provider,
            'provider_message_id' => $result->messageId, 'error_message' => $result->error,
            'sent_at' => $result->success ? now() : null,
        ]);

        return $log;
    }

    /** Data for the panel on Settings › Integrations. */
    public function panel(): array
    {
        return [
            'cutoff'        => $this->cutoff(),
            'buffer'        => (int) SystemConfig::getValue('close_of_business_buffer_minutes', 30),
            'fallback'      => (string) SystemConfig::getValue('close_of_business_fallback_time', '20:00'),
            'closed_today'  => BusinessDayClosure::whereDate('business_date', today())->first(),
            'recipients'    => $this->recipients(),
            'without_phone' => $this->recipientsWithoutPhone(),
            'recent'        => BusinessDayClosure::orderByDesc('business_date')->limit(7)->get(),
            'template_on'   => $this->notifier->render('close_of_business', []) !== null,
        ];
    }

    /** Active, non-system staff holding the permission. */
    private function holders(): Collection
    {
        return User::where('status', 'active')
            ->where('is_system_account', false)
            ->where(fn ($q) => $q->whereNull('domain')->orWhere('domain', 'system_user'))
            ->get()
            ->filter(fn (User $u) => $u->hasPermission(self::PERMISSION))
            ->values();
    }

    private function periodStart(CarbonImmutable $now): CarbonImmutable
    {
        $previous = BusinessDayClosure::where('business_date', '<', $now->toDateString())->orderByDesc('period_end')->value('period_end');

        return $previous ? CarbonImmutable::parse($previous) : $now->startOfDay();
    }
}
