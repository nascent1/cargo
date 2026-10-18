<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CargoPayment;
use App\Models\Consignment;
use App\Models\DualControlRequest;
use App\Models\Shift;
use App\Models\ShiftOvertimeRequest;
use App\Models\SystemConfig;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

/**
 * Shifts for sales-portal clerks (backend rules): a sales officer's shift starts when they sign in
 * (once 2FA is passed); anyone else who works in shifts starts one with their first booking action.
 * It runs for default_shift_hours. When it ends, booking, payment and cancelling answer
 * SHIFT_ENDED until the next shift may start (nextShiftFrom: midnight after the day it started,
 * and at least shift_rest_hours after it ended), unless an approved extension reopens it.
 * Midnight never ends a running shift (rotation 06–14, 14–22, 22–06). Signing out never ends a
 * shift. Administrators never work in shifts, and the admin booking terminal is never blocked.
 */
class ShiftService
{
    /** Roles that supervise rather than work in shifts. */
    public const EXEMPT_ROLES = ['super_admin', 'admin'];

    /** How a shift was started (audit log). */
    public const VIA_SIGN_IN = 'sign_in';
    public const VIA_FIRST_BOOKING = 'first_booking';

    public function isShiftManagementEnabled(): bool
    {
        return filter_var(SystemConfig::getValue('shift_management_enabled', true), FILTER_VALIDATE_BOOLEAN);
    }

    public function getDefaultShiftHours(): float
    {
        // Clamped: a mistyped value must not lock clerks out after minutes, or never
        return min(24.0, max(0.5, (float) SystemConfig::getValue('default_shift_hours', 8.0)));
    }

    /** Minimum break between two shifts (shift_rest_hours); 0 = the next shift may start at midnight. */
    public function restHours(): float
    {
        return max(0.0, (float) SystemConfig::getValue('shift_rest_hours', 8.0));
    }

    public function warningMinutes(): int
    {
        return max(0, (int) SystemConfig::getValue('shift_warning_minutes', 15));
    }

    /** Does this user work in shifts? Portal staff only; agents and administrators don't. */
    public function appliesTo(User $user): bool
    {
        return $this->isShiftManagementEnabled()
            && $user->domain === 'system_user'
            && !$user->is_system_account
            && !$user->hasAnyRole(self::EXEMPT_ROLES);
    }

    /**
     * Does signing in start this user's shift? Sales officers only: staff who work in shifts and
     * use the sales portal alone. Staff who also have the admin portal (port officers,
     * supervisors) start one with their first sales-portal booking instead.
     */
    public function startsOnSignIn(User $user): bool
    {
        return $this->appliesTo($user)
            && $user->hasPermission('portal:access')
            && !$user->hasPermission('admin_portal:access');
    }

    /**
     * A sales officer signed in: carry on the running shift, or start one — unless their last
     * shift ended within the rest period (then null; the portal shows "shift ended").
     */
    public function startOnSignIn(User $user): ?Shift
    {
        return $this->startsOnSignIn($user) ? $this->shiftForAction($user, self::VIA_SIGN_IN) : null;
    }

    public function getActiveShift(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)->active()->latest('start_time')->first();
    }

    public function latestShift(User $user): ?Shift
    {
        return Shift::where('user_id', $user->id)->latest('start_time')->first();
    }

    /**
     * The shift a booking action runs in: the active one, or a new one. Null when the clerk's
     * shift has ended and the rest period hasn't passed — the caller answers SHIFT_ENDED.
     */
    public function shiftForAction(User $user, string $via = self::VIA_FIRST_BOOKING): ?Shift
    {
        return DB::transaction(function () use ($user, $via) {
            // One clerk, one shift: serialise the clerk's concurrent first requests
            User::whereKey($user->id)->lockForUpdate()->first();

            $shift = $this->getActiveShift($user);
            if ($shift && $shift->end_time && $shift->end_time->isPast()) {
                $this->endShift($shift, Shift::END_EXPIRED, null, $shift->end_time);

                return null;
            }
            if ($shift) {
                return $shift;
            }

            return $this->isResting($user) ? null : $this->startShift($user, $via);
        });
    }

    /** The last shift ended and the next may not start yet: no new shift without an approved extension. */
    public function isResting(User $user): bool
    {
        $last = $this->latestShift($user);

        return $last && $last->status === 'ended' && $this->nextShiftFrom($last)?->isFuture();
    }

    /**
     * When the clerk may start a new shift after this one: the later of 00:00 after the day it
     * started and the minimum break after it ended. E.g. with an 8 h break: 06–14 → 00:00,
     * 14–22 → 06:00, 22–06 → 14:00 (in time for the next 22:00).
     */
    public function nextShiftFrom(Shift $shift): ?CarbonInterface
    {
        $endedAt = $shift->actual_end_time ?? $shift->end_time;
        if (!$endedAt) {
            return null;
        }
        $midnight = $shift->start_time->copy()->addDay()->startOfDay();
        $afterBreak = $endedAt->copy()->addMinutes((int) round($this->restHours() * 60));

        return $midnight->max($afterBreak);
    }

    public function startShift(User $user, string $via = self::VIA_FIRST_BOOKING): Shift
    {
        $active = $this->getActiveShift($user);
        if ($active) {
            return $active;
        }

        $start = now();

        $shift = Shift::create([
            'user_id' => $user->id,
            'start_time' => $start,
            'end_time' => $start->copy()->addMinutes((int) round($this->getDefaultShiftHours() * 60)),
            'status' => 'active',
        ]);
        AuditLog::createEntry($user->id, 'shift_start', 'shift', $shift->id, null,
            ['via' => $via, 'ends_at' => $shift->end_time->toIso8601String()], request()?->ip());

        return $shift;
    }

    public function endShift(Shift $shift, string $reason = Shift::END_SELF, ?int $endedBy = null, ?CarbonInterface $at = null): Shift
    {
        if ($shift->status === 'ended') {
            return $shift;
        }

        $shift->update([
            'status' => 'ended',
            'actual_end_time' => $at ?? now(),
            'end_reason' => $reason,
            'ended_by' => $endedBy,
        ]);

        return $shift;
    }

    /**
     * Close shifts whose time ran out while the clerk was idle (so "on shift now" and the
     * close-of-business count stay true without waiting for the clerk's next request).
     */
    public function closeExpired(): int
    {
        return Shift::active()->whereNotNull('end_time')->where('end_time', '<', now())
            ->update(['status' => 'ended', 'actual_end_time' => DB::raw('end_time'), 'end_reason' => Shift::END_EXPIRED, 'updated_at' => now()]);
    }

    /**
     * Apply an approved extension: add time to a running shift, or reopen an ended one from now.
     * Throws \DomainException when the extension no longer makes sense.
     */
    public function extend(Shift $shift, float $hours, ?int $approvedBy = null, ?string $reason = null): Shift
    {
        $minutes = (int) round($hours * 60);
        if ($minutes <= 0) {
            throw new \DomainException('The extension must add some time.');
        }

        $newer = Shift::where('user_id', $shift->user_id)->where('id', '!=', $shift->id)->where('start_time', '>', $shift->start_time)->exists();
        if ($newer) {
            throw new \DomainException('The clerk has started a newer shift since this request; nothing to extend.');
        }

        if ($shift->isActive()) {
            $shift->end_time = $shift->end_time->copy()->addMinutes($minutes);
        } else {
            $nextFrom = $this->nextShiftFrom($shift);
            if ($nextFrom && !$nextFrom->isFuture()) {
                throw new \DomainException('The clerk can already start a new shift (from ' . $nextFrom->format('d M H:i') . '): it starts when they next sign in or book, so there is nothing to extend.');
            }
            $shift->fill(['status' => 'active', 'end_time' => now()->addMinutes($minutes), 'actual_end_time' => null, 'end_reason' => null, 'ended_by' => null]);
        }
        $shift->extended_minutes = (int) $shift->extended_minutes + $minutes;
        $shift->save();

        // Overtime record for reporting (hours approved per clerk)
        ShiftOvertimeRequest::create([
            'user_id' => $shift->user_id, 'requested_hours' => round($minutes / 60, 2), 'status' => 'approved',
            'approved_by' => $approvedBy, 'approved_at' => now(), 'reason' => $reason,
        ]);

        return $shift;
    }

    /** The open extension request for a shift, if any. */
    public function pendingExtension(Shift $shift): ?DualControlRequest
    {
        return DualControlRequest::where('subject_key', $shift->subjectKey())
            ->where('operation_type', 'shift_extension')
            ->whereIn('status', ['pending', 'scheduled'])
            ->latest('id')
            ->first();
    }

    /**
     * What the clerk did in the shift — the cash handover sheet.
     *
     * Collections are paid cargo payments the clerk handled: confirmed by them (cash, Lipa Namba
     * checks) or, when the provider confirmed it, started by them (USSD push).
     */
    public function summary(Shift $shift): array
    {
        $from = $shift->start_time;
        $to = $shift->periodEnd();
        $userId = $shift->user_id;

        $booked = Consignment::where('booked_by_user_id', $userId)
            ->where('created_at', '>=', $from)->where('created_at', '<=', $to);

        $collections = CargoPayment::query()
            ->join('payment_methods', 'payment_methods.id', '=', 'cargo_payments.payment_method_id')
            ->where('cargo_payments.status', 'paid')
            ->where('cargo_payments.confirmed_at', '>=', $from)->where('cargo_payments.confirmed_at', '<=', $to)
            ->whereRaw('COALESCE(cargo_payments.confirmed_by_user_id, cargo_payments.initiated_by_user_id) = ?', [$userId])
            ->selectRaw('payment_methods.name as method, cargo_payments.flow, cargo_payments.currency, SUM(cargo_payments.amount) as total, COUNT(*) as n')
            ->groupBy('payment_methods.name', 'cargo_payments.flow', 'cargo_payments.currency')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($r) => [
                'method' => (string) $r->method, 'flow' => (string) $r->flow, 'currency' => (string) $r->currency,
                'total' => (float) $r->total, 'count' => (int) $r->n,
            ]);

        $sumByCurrency = fn ($rows) => $rows->groupBy('currency')->map(fn ($g) => round((float) $g->sum('total'), 2))->all();

        return [
            'from' => $from->toIso8601String(),
            'to' => $to->toIso8601String(),
            'bookings' => (clone $booked)->where('status', '!=', 'cancelled')->count(),
            'items' => (int) (clone $booked)->where('status', '!=', 'cancelled')->sum('total_items'),
            'weight_kg' => round((float) (clone $booked)->where('status', '!=', 'cancelled')->sum('total_weight_kg'), 2),
            'unpaid' => [
                'count' => (clone $booked)->where('status', '!=', 'cancelled')->where('payment_status', 'unpaid')->count(),
                'value' => $sumByCurrency((clone $booked)->where('status', '!=', 'cancelled')->where('payment_status', 'unpaid')
                    ->get(['currency', 'net_amount'])->map(fn ($c) => ['currency' => $c->currency ?? 'TZS', 'total' => (float) $c->net_amount])),
            ],
            'cancelled' => Consignment::where('booked_by_user_id', $userId)->where('cancelled_at', '>=', $from)->where('cancelled_at', '<=', $to)->count(),
            'collections' => $collections->values()->all(),
            'collected' => $sumByCurrency($collections),
            'cash_to_hand_over' => $sumByCurrency($collections->where('flow', CargoPayment::FLOW_CASH)),
        ];
    }

    /**
     * Headline figures for many shifts in two queries (lists and exports):
     * shift id => [bookings, collected [currency => amount], cash [currency => amount]].
     */
    public function totalsFor(iterable $shifts): array
    {
        $ids = collect($shifts)->pluck('id')->all();
        if (!$ids) {
            return [];
        }
        $in = implode(',', array_map('intval', $ids));
        $totals = array_fill_keys($ids, ['bookings' => 0, 'collected' => [], 'cash' => []]);

        $bookings = DB::select("
            SELECT s.id, COUNT(c.id) AS n
            FROM shifts s
            JOIN consignments c ON c.booked_by_user_id = s.user_id AND c.deleted_at IS NULL AND c.status <> 'cancelled'
                AND c.created_at >= s.start_time AND c.created_at <= COALESCE(s.actual_end_time, LEAST(s.end_time, NOW()), NOW())
            WHERE s.id IN ({$in})
            GROUP BY s.id");
        foreach ($bookings as $row) {
            $totals[$row->id]['bookings'] = (int) $row->n;
        }

        $money = DB::select("
            SELECT s.id, p.currency, SUM(p.amount) AS total, SUM(CASE WHEN p.flow = ? THEN p.amount ELSE 0 END) AS cash
            FROM shifts s
            JOIN cargo_payments p ON COALESCE(p.confirmed_by_user_id, p.initiated_by_user_id) = s.user_id AND p.status = 'paid'
                AND p.confirmed_at >= s.start_time AND p.confirmed_at <= COALESCE(s.actual_end_time, LEAST(s.end_time, NOW()), NOW())
            WHERE s.id IN ({$in})
            GROUP BY s.id, p.currency", [CargoPayment::FLOW_CASH]);
        foreach ($money as $row) {
            $totals[$row->id]['collected'][$row->currency] = (float) $row->total;
            if ((float) $row->cash > 0) {
                $totals[$row->id]['cash'][$row->currency] = (float) $row->cash;
            }
        }

        return $totals;
    }

    /**
     * Shift state for the clerk's portal: the current (or just-ended) shift, its figures and
     * any extension waiting for approval.
     */
    public function context(User $user): array
    {
        $applies = $this->appliesTo($user);
        $base = ['applies' => $applies, 'warning_minutes' => $this->warningMinutes(), 'shift_hours' => $this->getDefaultShiftHours()];
        if (!$applies) {
            return $base + ['state' => 'none', 'shift' => null];
        }

        $shift = $this->getActiveShift($user);
        if ($shift && $shift->end_time?->isPast()) {
            $this->endShift($shift, Shift::END_EXPIRED, null, $shift->end_time);
        }
        $shift ??= $this->latestShift($user);

        $state = match (true) {
            !$shift => 'not_started',
            $shift->status === 'active' => 'active',
            $this->isResting($user) => 'ended',
            default => 'not_started', // the next shift may start: the next sign-in (or booking) starts it
        };
        if ($state === 'not_started') {
            return $base + ['state' => $state, 'shift' => null];
        }

        $pending = $this->pendingExtension($shift);

        return $base + [
            'state' => $state,
            'shift' => [
                'id' => $shift->id,
                'started_at' => $shift->start_time->toIso8601String(),
                'ends_at' => $shift->end_time?->toIso8601String(),
                'ended_at' => $shift->actual_end_time?->toIso8601String(),
                'remaining_minutes' => $state === 'active' && $shift->end_time ? max(0, (int) now()->diffInMinutes($shift->end_time, false)) : 0,
                'extended_minutes' => (int) $shift->extended_minutes,
                'end_reason' => $shift->end_reason,
                'next_shift_at' => $state === 'ended' ? $this->nextShiftFrom($shift)?->toIso8601String() : null,
            ],
            'summary' => $this->summary($shift),
            'pending_extension' => $pending ? [
                'id' => $pending->id,
                'hours' => (float) ($pending->payload_after['hours'] ?? 0),
                'reason' => $pending->payload_after['reason'] ?? null,
                'submitted_at' => $pending->created_at->toIso8601String(),
            ] : null,
        ];
    }
}
