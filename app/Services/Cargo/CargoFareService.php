<?php

namespace App\Services\Cargo;

use App\Models\CargoFare;
use App\Models\CargoItem;
use App\Models\CargoType;
use App\Models\DualControlRequest;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Plans and applies changes to cargo fares. Fares are versions with a time window; nothing is edited in place.
 *
 * Every change is first turned into a plan — which fares close, which scheduled fares are cancelled,
 * which new versions are created — plus alerts for the person saving it. The same plan is recomputed
 * when the change is applied (on save, approval or schedule); if it no longer matches what was
 * shown, the change is refused rather than applied differently.
 *
 * Intents (what the person asked for):
 *  create / revise: cargo_type_id, route_id, transport_unit_id, prices{}, starts_at|null, ends_on|null,
 *                   replace, resume, reason, revising_id
 *  end:             fare_id, ends_at|null (null = now), revoke, reason
 *  delete:          fare_id, reason
 *  bulk:            cargo_type_id|null, route_id|null, value_charge_pct, starts_at|null, reason
 *  copy:            source_route_id, target_route_id, type_ids[]|null, adjust_pct, starts_at|null, existing (skip|replace), reason
 */
class CargoFareService
{
    public const OPERATIONS = [
        'create' => 'cargo_fare_create',
        'revise' => 'cargo_fare_revise',
        'end' => 'cargo_fare_end',
        'delete' => 'cargo_fare_delete',
        'bulk' => 'cargo_fare_bulk_update',
        'copy' => 'cargo_fare_copy',
    ];

    private const UNIT_LABEL = ['kg' => 'kg', 'cbm' => 'cubic metre', 'unit' => 'piece', 'lane_meter' => 'vehicle'];

    /** Kind of the operation being planned (approval wording) */
    private string $kind = 'create';

    /** The request being applied, so its own open change doesn't block it */
    private ?int $ignoreRequestId = null;

    public function __construct(private DualControlService $dualControl)
    {
    }

    // ── Validation ────────────────────────────────────────────

    /**
     * The fare form (create / revise) as an intent. Validation errors use the form's field names.
     */
    public function fareIntent(array $input, ?CargoFare $revising = null): array
    {
        $rules = [
            // An edit keeps the fare's type, route and vessel (taken from the fare below)
            'cargo_type_id' => ($revising ? 'nullable' : 'required') . '|exists:cargo_types,id',
            'route_id' => ($revising ? 'nullable' : 'required') . '|exists:routes,id',
            'transport_unit_id' => 'nullable|exists:transport_units,id',
            'price_tzs' => 'nullable|numeric|min:0',
            'price_usd' => 'nullable|numeric|min:0',
            'price_per_kg_tzs' => 'nullable|numeric|min:0',
            'price_per_kg_usd' => 'nullable|numeric|min:0',
            'price_per_cbm_tzs' => 'nullable|numeric|min:0',
            'price_per_cbm_usd' => 'nullable|numeric|min:0',
            'min_charge_tzs' => 'nullable|numeric|min:0',
            'min_charge_usd' => 'nullable|numeric|min:0',
            'value_charge_pct' => 'nullable|numeric|min:0|max:100',
            'ends_on' => 'nullable|date',
            'replace' => 'boolean',
            'resume' => 'boolean',
            'reason' => ($revising ? 'required' : 'nullable') . '|string|min:5|max:255',
        ];
        $v = validator($input, $rules, [
            'route_id.required' => 'Choose the route this fare applies to.',
            'value_charge_pct.max' => 'The value charge is a percentage — enter 0 to 100.',
            'reason.required' => 'Say why the fare is changing (kept with the fare history).',
            'reason.min' => 'Give a reason of at least 5 characters.',
        ])->validate();

        if ($revising) {
            // The series can't change on an edit: that is a different fare
            $v['cargo_type_id'] = $revising->cargo_type_id;
            $v['route_id'] = $revising->route_id;
            $v['transport_unit_id'] = $revising->transport_unit_id;
        }

        $type = CargoType::findOrFail($v['cargo_type_id']);
        $field = CargoFare::basisField($type->measurement_unit);
        if ((float) ($v[$field] ?? 0) <= 0) {
            $label = ['price_per_kg_tzs' => 'per-kg rate (TZS)', 'price_per_cbm_tzs' => 'per-CBM rate (TZS)', 'price_tzs' => 'flat price (TZS)'][$field];
            throw ValidationException::withMessages([$field => "{$type->name} is priced per " . (self::UNIT_LABEL[$type->measurement_unit] ?? $type->measurement_unit) . " — enter the {$label}."]);
        }

        $prices = [];
        foreach (CargoFare::PRICE_FIELDS as $f) {
            $value = $v[$f] ?? null;
            $prices[$f] = in_array($f, ['price_tzs', 'price_usd', 'value_charge_pct'], true) ? (float) ($value ?? 0) : ($value === null || (float) $value <= 0 ? null : (float) $value);
        }

        return [
            'cargo_type_id' => (int) $v['cargo_type_id'],
            'route_id' => (int) $v['route_id'],
            'transport_unit_id' => !empty($v['transport_unit_id']) ? (int) $v['transport_unit_id'] : null,
            'prices' => $prices,
            'ends_on' => $v['ends_on'] ?? null,
            'replace' => (bool) ($v['replace'] ?? false),
            'resume' => (bool) ($v['resume'] ?? true),
            'reason' => $v['reason'] ?? null,
            'revising_id' => $revising?->id,
        ];
    }

    // ── Planning ──────────────────────────────────────────────

    /**
     * Plan an operation. $startsAt null = "immediately" (now, or the moment of approval).
     *
     * @return array{operation: string, series: array, blocking: array, alerts: array, needs_replace: bool, lines_kept: int, hash: string, starts_at: ?string, summary: string}
     */
    public function plan(string $kind, array $intent, ?CarbonInterface $startsAt = null): array
    {
        $this->kind = $kind;
        // Whole seconds: windows are stored to the second, so a sub-second start could round onto another fare's
        $start = $startsAt && $startsAt->isFuture() ? Carbon::instance($startsAt)->startOfSecond() : now()->startOfSecond();
        $startIsNow = !$startsAt || !$startsAt->isFuture();

        $series = match ($kind) {
            'create', 'revise' => [$this->planFare($intent, $start, $startIsNow)],
            'end' => [$this->planEnd($intent, $start)],
            'delete' => [$this->planDelete($intent)],
            'bulk' => $this->planBulk($intent, $start, $startIsNow),
            'copy' => $this->planCopy($intent, $start, $startIsNow),
        };

        foreach ($series as &$one) {
            $one['alerts'] = array_values(array_map('unserialize', array_unique(array_map('serialize', $one['alerts']))));
        }
        unset($one);

        $blocking = array_merge(...array_map(fn ($s) => $s['blocking'], $series ?: [['blocking' => []]]));
        if (in_array($kind, ['bulk', 'copy'], true) && !array_filter($series, fn ($s) => $s['actions'])) {
            $blocking[] = $kind === 'bulk' ? 'No fare in this selection needs a change.' : 'Nothing to copy: no fares match.';
        }

        $plan = [
            'operation' => self::OPERATIONS[$kind],
            'kind' => $kind,
            'starts_at' => $startIsNow ? null : $start->toIso8601String(),
            'series' => $series,
            'blocking' => array_values(array_unique($blocking)),
            'needs_replace' => (bool) array_filter($series, fn ($s) => $s['needs_replace']),
            'replace_missing' => (bool) array_filter($series, fn ($s) => $s['replace_missing'] ?? false),
            'lines_kept' => array_sum(array_column($series, 'lines_kept')),
            'requires_approval' => $this->dualControl->requiresDualControl(self::OPERATIONS[$kind]),
        ];
        $plan['hash'] = $this->signature($plan);

        return $plan;
    }

    /**
     * A new version for one type + route + vessel (create or revise).
     */
    private function planFare(array $intent, Carbon $start, bool $startIsNow): array
    {
        $type = CargoType::findOrFail($intent['cargo_type_id']);
        $route = Route::findOrFail($intent['route_id']);
        $unit = $intent['transport_unit_id'] ? TransportUnit::find($intent['transport_unit_id']) : null;
        $s = $this->seriesInfo($type, $route, $unit);
        $prices = $intent['prices'];
        $revising = !empty($intent['revising_id']) ? CargoFare::withTrashed()->find($intent['revising_id']) : null;

        if ($revising && !in_array($revising->state, ['current', 'scheduled'], true)) {
            $s['blocking'][] = "Only current or scheduled fares can be changed — this one is {$revising->state}. Add a new fare instead.";

            return $s;
        }

        // Ends on a day = stops at the start of the next day; an edit keeps the edited fare's end
        if (!empty($intent['ends_on'])) {
            $end = Carbon::parse($intent['ends_on'])->startOfDay()->addDay();
        } else {
            $end = $revising?->effective_until ? Carbon::instance($revising->effective_until) : null;
        }

        if ($end && $end->lte($start)) {
            $s['blocking'][] = 'The end must be after the start.';

            return $s;
        }

        $this->assertNoOpenChange($s, $revising);

        $overlapping = $this->overlapping($intent['cargo_type_id'], $intent['route_id'], $intent['transport_unit_id'], $start, $end);
        $newLabel = $this->priceLabel($type, $prices);

        // Same prices already in place for the whole window
        foreach ($overlapping as $f) {
            if ($this->samePrices($f, $prices) && $f->effective_from->lte($start) && (!$f->effective_until || ($end && $f->effective_until->gte($end)))) {
                $s['blocking'][] = $revising && $f->id === $revising->id
                    ? 'Nothing changed — the prices are the same as the fare you are editing.'
                    : "This exact fare is already in place (#{$f->id}, {$this->window($f)}).";

                return $s;
            }
        }

        $createdFrom = null;
        foreach ($overlapping as $f) {
            $isRevised = $revising && $f->id === $revising->id;
            $fLabel = $this->priceLabel($type, $f->prices());

            if ($f->effective_from->lt($start)) {
                // Started before the new fare: it ends when the new one starts
                $s['actions'][] = ['do' => 'close', 'fare_id' => $f->id, 'at' => 'start'];
                $s['lines_kept'] += $f->items()->count();
                $createdFrom ??= $f->id;

                $s['alerts'][] = ['level' => 'amber', 'text' => ($f->state === 'current' ? 'The current fare ' : "The scheduled fare from {$this->fmt($f->effective_from)} ")
                    . "{$fLabel} " . ($startIsNow ? 'stops applying ' . ($this->needsApproval() ? 'when this is approved' : 'now') : 'expires ' . $this->fmtEnd($start))
                    . ". The new fare {$newLabel}" . $this->delta($type, $f->prices(), $prices) . ' ' . $this->startPhrase($start, $startIsNow) . '.'];

                // It went on beyond the new fare's end: resume its price afterwards
                if ($end && (!$f->effective_until || $f->effective_until->gt($end))) {
                    if ($intent['resume'] ?? true) {
                        $s['actions'][] = ['do' => 'create', 'kind' => 'resume', 'copy_of' => $f->id, 'from' => $end->toIso8601String(), 'until' => $f->effective_until?->toIso8601String()];
                        $s['alerts'][] = ['level' => 'amber', 'text' => "{$fLabel} resumes on {$this->fmt($end)}" . ($f->effective_until ? " until {$this->fmtEnd($f->effective_until)}" : '') . '.'];
                    } else {
                        $s['alerts'][] = ['level' => 'amber', 'text' => "After {$this->fmtEnd($end)} there is no fare for {$type->name} on {$route->name} — bookings will be refused until a fare is added."];
                    }
                }
            } else {
                // Starts on or after the new start: it would be replaced (cancelled), never edited
                $sameStart = $f->effective_from->eq($start);
                if (!$isRevised) {
                    $s['needs_replace'] = true;
                    $s['replace_missing'] = !($intent['replace'] ?? false);
                }
                $s['actions'][] = ['do' => 'cancel', 'fare_id' => $f->id];
                $s['alerts'][] = ['level' => $isRevised ? 'amber' : 'red', 'text' => $isRevised
                    ? "The scheduled fare {$fLabel} from {$this->fmt($f->effective_from)} is replaced by this version and kept in the history as cancelled."
                    : ($sameStart
                        ? "A fare is already configured from {$this->fmt($f->effective_from)}: {$fLabel}. Replacing it cancels it (it stays in the history)."
                        : "The scheduled fare {$fLabel} from {$this->fmt($f->effective_from)} falls inside these dates and would be cancelled.")];
                $createdFrom ??= $f->id;

                // Part of it lies beyond the new fare's end: keep that part
                if ($end && (!$f->effective_until || $f->effective_until->gt($end))) {
                    $s['actions'][] = ['do' => 'create', 'kind' => 'resume', 'copy_of' => $f->id, 'from' => $end->toIso8601String(), 'until' => $f->effective_until?->toIso8601String()];
                    $s['alerts'][] = ['level' => 'amber', 'text' => "{$fLabel} still applies from {$this->fmt($end)}."];
                }
            }
        }

        $s['actions'][] = [
            'do' => 'create', 'kind' => $revising ? 'revision' : 'new', 'prices' => $prices,
            'from' => $startIsNow ? 'start' : $start->toIso8601String(), 'until' => $end?->toIso8601String(),
            'previous_fare_id' => $revising?->id ?? $createdFrom,
        ];
        // The new fare goes before resumes / kept parts so it is created first
        $s['actions'] = $this->ordered($s['actions']);

        if (!$overlapping->count()) {
            $s['alerts'][] = ['level' => 'green', 'text' => "{$type->name} on {$route->name}" . ($unit ? " ({$unit->name})" : '') . ": {$newLabel} " . $this->startPhrase($start, $startIsNow) . ($end ? ' until ' . $this->fmtEnd($end) : '') . '.'];
        }
        if ($end && !$overlapping->first(fn ($f) => !$f->effective_until || $f->effective_until->gt($end)) && !CargoFare::series($type->id, $route->id, $unit?->id)->where('status', CargoFare::ACTIVE)->where('effective_from', '>=', $end)->exists()) {
            $s['alerts'][] = ['level' => 'amber', 'text' => "After {$this->fmtEnd($end)} there is no fare for {$type->name} on {$route->name} — bookings will be refused until a fare is added."];
        }
        if ($s['lines_kept']) {
            $s['alerts'][] = ['level' => 'info', 'text' => "{$s['lines_kept']} booked line" . ($s['lines_kept'] === 1 ? ' was' : 's were') . ' priced on the fare being replaced. They keep the price they were charged.'];
        }
        if (!$unit) {
            $own = CargoFare::live()->where('cargo_type_id', $type->id)->where('route_id', $route->id)->whereNotNull('transport_unit_id')->with('transportUnit')->get();
            foreach ($own as $vf) {
                $s['alerts'][] = ['level' => 'info', 'text' => "{$vf->transportUnit?->name} has its own fare for {$type->name} on this route ({$this->priceLabel($type, $vf->prices())}); that vessel keeps it."];
            }
        }

        return $s;
    }

    private function planEnd(array $intent, Carbon $at): array
    {
        $fare = CargoFare::with(['cargoType', 'route', 'transportUnit'])->findOrFail($intent['fare_id']);
        $s = $this->seriesInfo($fare->cargoType, $fare->route, $fare->transportUnit);
        $label = $this->priceLabel($fare->cargoType, $fare->prices());
        $revoke = (bool) ($intent['revoke'] ?? false);
        // A chosen end that has already passed (approved late) ends it now
        $endAt = !empty($intent['ends_at']) && !$revoke ? Carbon::parse($intent['ends_at'])->max($at) : $at;

        if (!in_array($fare->state, ['current', 'scheduled'], true)) {
            $s['blocking'][] = "This fare is already {$fare->state}.";

            return $s;
        }
        $this->assertNoOpenChange($s, $fare);

        if ($fare->effective_until && $endAt->gte($fare->effective_until)) {
            $s['blocking'][] = 'The fare already ends on ' . $this->fmtEnd($fare->effective_until) . '.';

            return $s;
        }

        $lines = $fare->items()->count();
        if ($fare->effective_from->gte($endAt)) {
            $s['actions'][] = ['do' => 'cancel', 'fare_id' => $fare->id, 'revoke' => $revoke];
            $s['alerts'][] = ['level' => 'amber', 'text' => "The scheduled fare {$label} from {$this->fmt($fare->effective_from)} is withdrawn before it starts (kept in the history as " . ($revoke ? 'revoked' : 'cancelled') . ').'];
        } else {
            $s['actions'][] = ['do' => 'end', 'fare_id' => $fare->id, 'at' => $endAt->gt($at) ? $endAt->toIso8601String() : 'start', 'revoke' => $revoke];
            $s['alerts'][] = ['level' => $revoke ? 'red' : 'amber', 'text' => ($revoke ? 'Revoke: ' : '') . "{$label} stops applying "
                . (!empty($intent['ends_at']) && !$revoke ? 'after ' . $this->fmtEnd($endAt) : ($this->needsApproval() ? 'when this is approved' : 'now')) . '.'];
            $s['lines_kept'] = $lines;
            if ($lines) {
                $s['alerts'][] = ['level' => $revoke ? 'red' : 'info', 'text' => $revoke
                    ? "{$lines} booked line" . ($lines === 1 ? ' was' : 's were') . ' priced on this fare. They keep the price charged; review them from the fare history.'
                    : "{$lines} booked line" . ($lines === 1 ? ' was' : 's were') . ' priced on this fare. They keep their price.'];
            }
        }

        $next = CargoFare::series($fare->cargo_type_id, $fare->route_id, $fare->transport_unit_id)
            ->where('status', CargoFare::ACTIVE)->where('id', '!=', $fare->id)->where('effective_from', '>=', $endAt)->orderBy('effective_from')->first();
        $s['alerts'][] = $next
            ? ['level' => 'info', 'text' => "The next fare ({$this->priceLabel($fare->cargoType, $next->prices())}) starts {$this->fmt($next->effective_from)}."]
            : ['level' => 'amber', 'text' => "After that, {$fare->cargoType->name} can't be booked on {$fare->route->name}" . ($fare->transportUnit ? " ({$fare->transportUnit->name})" : '') . ' until a fare is added.'];

        return $s;
    }

    private function planDelete(array $intent): array
    {
        $fare = CargoFare::with(['cargoType', 'route', 'transportUnit'])->findOrFail($intent['fare_id']);
        $s = $this->seriesInfo($fare->cargoType, $fare->route, $fare->transportUnit);

        if ($fare->items()->exists()) {
            $s['blocking'][] = 'Bookings were priced on this fare, so it stays in the history. End it instead.';
        } elseif (!in_array($fare->state, ['scheduled', 'cancelled', 'inactive'], true)) {
            $s['blocking'][] = 'This fare has already started. End it instead, so its history is kept.';
        } else {
            $this->assertNoOpenChange($s, $fare);
            $s['actions'][] = ['do' => 'delete', 'fare_id' => $fare->id];
            $s['alerts'][] = ['level' => 'amber', 'text' => "The unused fare {$this->priceLabel($fare->cargoType, $fare->prices())} ({$this->window($fare)}) is deleted."];
        }

        return $s;
    }

    /**
     * Value charge on every live fare in the selection: each gets a new version with the new %.
     */
    private function planBulk(array $intent, Carbon $start, bool $startIsNow): array
    {
        $pct = (float) $intent['value_charge_pct'];
        $fares = CargoFare::with(['cargoType', 'route', 'transportUnit'])->live()
            ->when($intent['cargo_type_id'] ?? null, fn ($q, $id) => $q->where('cargo_type_id', $id))
            ->when($intent['route_id'] ?? null, fn ($q, $id) => $q->where('route_id', $id))
            ->orderBy('cargo_type_id')->orderBy('route_id')->orderBy('effective_from')->get();

        $series = [];
        foreach ($fares as $fare) {
            if (abs((float) $fare->value_charge_pct - $pct) < 0.001) {
                continue;
            }
            $prices = array_merge($fare->prices(), ['value_charge_pct' => $pct]);
            $fareStart = $fare->effective_from->gt($start) ? Carbon::instance($fare->effective_from) : $start;
            $plan = $this->planFare([
                'cargo_type_id' => $fare->cargo_type_id, 'route_id' => $fare->route_id, 'transport_unit_id' => $fare->transport_unit_id,
                'prices' => $prices, 'ends_on' => null, 'replace' => false, 'resume' => true,
                'reason' => $intent['reason'] ?? null, 'revising_id' => $fare->id,
            ], $fareStart, $startIsNow && $fareStart->eq($start));
            $series[] = $plan;
        }

        return $series;
    }

    /**
     * Route-wide fares of one route copied to another, optionally adjusted by a percentage.
     */
    private function planCopy(array $intent, Carbon $start, bool $startIsNow): array
    {
        $adjust = 1 + ((float) ($intent['adjust_pct'] ?? 0)) / 100;
        $source = CargoFare::with('cargoType')->effectiveAt($start)
            ->where('route_id', $intent['source_route_id'])->whereNull('transport_unit_id')
            ->when($intent['type_ids'] ?? null, fn ($q, $ids) => $q->whereIn('cargo_type_id', $ids))
            ->orderBy('cargo_type_id')->get();

        $series = [];
        foreach ($source as $fare) {
            $prices = collect($fare->prices())->map(fn ($v, $f) => $v === null || $f === 'value_charge_pct' ? $v : round($v * $adjust, str_contains($f, '_usd') ? 2 : 0))->all();
            $existing = CargoFare::live()->series($fare->cargo_type_id, (int) $intent['target_route_id'], null)->exists();
            if ($existing && ($intent['existing'] ?? 'skip') === 'skip') {
                $s = $this->seriesInfo($fare->cargoType, Route::find($intent['target_route_id']), null);
                $s['skipped'] = true;
                $s['alerts'][] = ['level' => 'info', 'text' => "{$fare->cargoType->name}: already priced on the target route — skipped."];
                $series[] = $s;
                continue;
            }
            $series[] = $this->planFare([
                'cargo_type_id' => $fare->cargo_type_id, 'route_id' => (int) $intent['target_route_id'], 'transport_unit_id' => null,
                'prices' => $prices, 'ends_on' => null, 'replace' => true, 'resume' => true,
                'reason' => $intent['reason'] ?? null, 'revising_id' => null,
            ], $start, $startIsNow);
        }

        return $series;
    }

    // ── Applying ──────────────────────────────────────────────

    /**
     * Apply a plan that was recomputed for the moment it takes effect. Refuses when it differs
     * from the plan the maker saw ($expectedHash), so nothing is ever applied that nobody reviewed.
     */
    public function apply(string $kind, array $intent, CarbonInterface $effectiveAt, ?string $expectedHash, ?DualControlRequest $request = null): array
    {
        $startsAt = !empty($intent['starts_at']) ? Carbon::parse($intent['starts_at']) : null;
        if (in_array($kind, ['end'], true)) {
            $startsAt = null; // the end moment is inside the intent
        }
        // The requested start; plan() treats one that has passed (approved late) as now
        $this->ignoreRequestId = $request?->id;
        try {
            $plan = $this->plan($kind, $intent, $startsAt);
        } finally {
            $this->ignoreRequestId = null;
        }

        if ($plan['blocking']) {
            throw new DualControlException(implode(' ', $plan['blocking']));
        }
        if ($plan['replace_missing']) {
            throw new DualControlException('Another fare now falls inside these dates. Reject this request and submit it again to review the change.');
        }
        if ($expectedHash && $plan['hash'] !== $expectedHash) {
            throw new DualControlException('The fares involved changed after this was submitted, so applying it would do something different from what was reviewed. Reject it and submit the change again.');
        }

        $start = $plan['starts_at'] ? Carbon::parse($plan['starts_at']) : Carbon::instance($effectiveAt)->startOfSecond();
        $userId = $request?->maker_id ?? auth()->id();
        $enderId = $request?->checker_id ?? $userId;
        $created = [];

        DB::transaction(function () use ($plan, $start, $userId, $enderId, $request, $intent, &$created) {
            foreach ($plan['series'] as $s) {
                $newId = null;
                foreach ($s['actions'] as $a) {
                    $at = fn ($v) => $v === 'start' ? $start : ($v ? Carbon::parse($v) : null);
                    switch ($a['do']) {
                        case 'close':
                        case 'end':
                            $fare = CargoFare::lockForUpdate()->findOrFail($a['fare_id']);
                            $fare->update([
                                'effective_until' => $at($a['at']),
                                'status' => !empty($a['revoke']) ? CargoFare::REVOKED : $fare->status,
                                'ended_by' => $enderId, 'ended_at' => now(),
                                'change_reason' => $a['do'] === 'end' ? ($intent['reason'] ?? $fare->change_reason) : $fare->change_reason,
                            ]);
                            break;
                        case 'cancel':
                            CargoFare::lockForUpdate()->findOrFail($a['fare_id'])->update([
                                'status' => !empty($a['revoke']) ? CargoFare::REVOKED : CargoFare::CANCELLED,
                                'ended_by' => $enderId, 'ended_at' => now(),
                            ]);
                            break;
                        case 'delete':
                            $fare = CargoFare::findOrFail($a['fare_id']);
                            $fare->update(['status' => CargoFare::CANCELLED, 'ended_by' => $enderId, 'ended_at' => now(), 'change_reason' => $intent['reason'] ?? 'Deleted (never used)']);
                            $fare->delete();
                            break;
                        case 'create':
                            $source = !empty($a['copy_of']) ? CargoFare::withTrashed()->find($a['copy_of']) : null;
                            $prices = $a['prices'] ?? $source->prices();
                            $fare = CargoFare::create(array_merge($prices, [
                                'cargo_type_id' => $s['cargo_type_id'],
                                'route_id' => $s['route_id'],
                                'transport_unit_id' => $s['transport_unit_id'],
                                'status' => CargoFare::ACTIVE,
                                'effective_from' => $at($a['from']),
                                'effective_until' => $at($a['until']),
                                'previous_fare_id' => $a['kind'] === 'resume' ? ($newId ?? $a['copy_of']) : ($a['previous_fare_id'] ?? null),
                                'version' => $this->nextVersion($s['cargo_type_id'], $s['route_id'], $s['transport_unit_id']),
                                'change_reason' => $a['kind'] === 'resume'
                                    ? "Price of #{$a['copy_of']} continues after the fare #" . ($newId ?? '?') . ' ends'
                                    : ($intent['reason'] ?? null),
                                'created_by' => $userId,
                                'dual_control_request_id' => $request?->id,
                            ]));
                            $created[] = $fare->id;
                            if ($a['kind'] !== 'resume') {
                                $newId = $fare->id;
                            }
                            break;
                    }
                }
            }
        });

        return ['created' => $created, 'plan' => $plan];
    }

    // ── Reading ───────────────────────────────────────────────

    /**
     * Every active cargo type's price on one route: the fare in force now, the next scheduled one
     * and vessel-specific fares (the route profile's Prices tab).
     *
     * @return \Illuminate\Support\Collection<int, array{type: CargoType, current: ?CargoFare, next: ?CargoFare, vessels: \Illuminate\Support\Collection}>
     */
    public function routeRateCard(Route $route)
    {
        $fares = CargoFare::with('transportUnit:id,name')->live()->where('route_id', $route->id)->orderBy('effective_from')->get();

        return CargoType::where('status', 'active')->orderBy('category')->orderBy('sort_order')->orderBy('name')->get()
            ->map(fn (CargoType $type) => [
                'type' => $type,
                'current' => $fares->first(fn ($f) => $f->cargo_type_id === $type->id && !$f->transport_unit_id && $f->state === 'current'),
                'next' => $fares->first(fn ($f) => $f->cargo_type_id === $type->id && !$f->transport_unit_id && $f->state === 'scheduled'),
                'vessels' => $fares->filter(fn ($f) => $f->cargo_type_id === $type->id && $f->transport_unit_id)->values(),
            ]);
    }

    // ── Pieces ────────────────────────────────────────────────

    /**
     * Fields every dual-control request for a fare carries, so the generic diff is readable too.
     */
    public function displayPayload(array $plan): array
    {
        $first = $plan['series'][0] ?? [];

        return array_filter([
            'cargo_type' => $first['type_name'] ?? null,
            'route' => $first['route_name'] ?? null,
            'vessel' => $first['unit_name'] ?? (isset($first['route_name']) ? 'All vessels' : null),
            'fares_affected' => count($plan['series']) > 1 ? count($plan['series']) : null,
        ], fn ($v) => $v !== null);
    }

    public function seriesKeys(array $plan): array
    {
        return array_values(array_unique(array_map(fn ($s) => $s['key'], $plan['series'])));
    }

    private function seriesInfo(?CargoType $type, ?Route $route, ?TransportUnit $unit): array
    {
        return [
            'key' => CargoFare::seriesKey($type?->id ?? 0, $route?->id ?? 0, $unit?->id),
            'cargo_type_id' => $type?->id, 'route_id' => $route?->id, 'transport_unit_id' => $unit?->id,
            'type_name' => $type?->name, 'route_name' => $route?->name, 'unit_name' => $unit?->name,
            'measurement_unit' => $type?->measurement_unit,
            'actions' => [], 'alerts' => [], 'blocking' => [], 'needs_replace' => false, 'replace_missing' => false, 'lines_kept' => 0,
        ];
    }

    /**
     * Blocks a second change while one for the same fare series is pending or scheduled
     * (a single fare change, or a bulk / copy request that includes this series).
     */
    private function assertNoOpenChange(array &$s, ?CargoFare $fare = null): void
    {
        $open = $this->dualControl->openChangeFor($s['key']);
        if ($open && $open->id === $this->ignoreRequestId) {
            $open = null;
        }
        $open ??= DualControlRequest::open()
            ->when($this->ignoreRequestId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->whereIn('operation_type', [self::OPERATIONS['bulk'], self::OPERATIONS['copy']])
            ->whereRaw("(payload_after::jsonb -> 'series_keys') @> jsonb_build_array(?::text)", [$s['key']])
            ->where(fn ($q) => $q->where('status', 'scheduled')->orWhere('expires_at', '>', now()))
            ->first();

        if ($open) {
            $s['blocking'][] = $this->dualControl->openChangeMessage($open);
        }
    }

    private function overlapping(int $typeId, int $routeId, ?int $unitId, Carbon $start, ?Carbon $end)
    {
        return CargoFare::series($typeId, $routeId, $unitId)
            ->where('status', CargoFare::ACTIVE)
            ->where(fn ($q) => $q->whereNull('effective_until')->orWhere('effective_until', '>', $start))
            ->when($end, fn ($q) => $q->where('effective_from', '<', $end))
            ->orderBy('effective_from')
            ->get();
    }

    private function nextVersion(int $typeId, int $routeId, ?int $unitId): int
    {
        return (int) CargoFare::withTrashed()->series($typeId, $routeId, $unitId)->max('version') + 1;
    }

    private function samePrices(CargoFare $fare, array $prices): bool
    {
        foreach (CargoFare::PRICE_FIELDS as $f) {
            if (abs((float) ($fare->{$f} ?? 0) - (float) ($prices[$f] ?? 0)) > 0.0001) {
                return false;
            }
        }

        return true;
    }

    /**
     * What decides the result: the actions, fares touched, prices and fixed end times — not the start
     * moment (that moves when approval is late) and not alert wording.
     */
    private function signature(array $plan): string
    {
        $sig = array_map(fn ($s) => [
            $s['key'],
            array_map(fn ($a) => Arr::only($a, ['do', 'kind', 'fare_id', 'copy_of', 'prices', 'until', 'revoke']), $s['actions']),
        ], $plan['series']);

        return sha1(json_encode([$plan['operation'], $sig]));
    }

    /**
     * Closing and cancelling first, then the new fare, then copies that continue after it.
     */
    private function ordered(array $actions): array
    {
        $rank = fn ($a) => match (true) {
            in_array($a['do'], ['close', 'cancel', 'end', 'delete'], true) => 0,
            ($a['kind'] ?? null) === 'resume' => 2,
            default => 1,
        };
        usort($actions, fn ($x, $y) => $rank($x) <=> $rank($y));

        return $actions;
    }

    private function needsApproval(): bool
    {
        return $this->dualControl->requiresDualControl(self::OPERATIONS[$this->kind]);
    }

    public function priceLabel(CargoType $type, array $prices): string
    {
        $field = CargoFare::basisField($type->measurement_unit);
        $text = 'TZS ' . number_format((float) ($prices[$field] ?? 0)) . ' / ' . (self::UNIT_LABEL[$type->measurement_unit] ?? $type->measurement_unit);
        if ((float) ($prices['value_charge_pct'] ?? 0) > 0) {
            $text .= ' + ' . rtrim(rtrim(number_format((float) $prices['value_charge_pct'], 2), '0'), '.') . '% of value';
        }

        return $text;
    }

    private function delta(CargoType $type, array $old, array $new): string
    {
        $field = CargoFare::basisField($type->measurement_unit);
        $a = (float) ($old[$field] ?? 0);
        $b = (float) ($new[$field] ?? 0);
        if ($a <= 0 || abs($a - $b) < 0.0001) {
            return '';
        }
        $pct = ($b - $a) / $a * 100;

        return ' (' . ($pct > 0 ? '+' : '') . rtrim(rtrim(number_format($pct, 1), '0'), '.') . '%)';
    }

    private function startPhrase(Carbon $start, bool $startIsNow): string
    {
        if ($startIsNow) {
            return $this->needsApproval() ? 'starts when approved' : 'starts now';
        }

        return 'starts ' . $this->fmt($start);
    }

    private function window(CargoFare $f): string
    {
        return 'from ' . $this->fmt($f->effective_from) . ($f->effective_until ? ' until ' . $this->fmtEnd($f->effective_until) : ', no end');
    }

    private function fmt(CarbonInterface $t): string
    {
        return $t->format('d M Y H:i');
    }

    /**
     * The last minute a window covers (its end is exclusive): 1 Nov 00:00 shows as 31 Oct 23:59.
     */
    private function fmtEnd(CarbonInterface $t): string
    {
        return $t->copy()->subMinute()->format('d M Y H:i');
    }
}
