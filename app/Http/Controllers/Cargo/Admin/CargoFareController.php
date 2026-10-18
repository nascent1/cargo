<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\CargoFare;
use App\Models\CargoItem;
use App\Models\CargoType;
use App\Models\DualControlRequest;
use App\Models\Route;
use App\Services\Cargo\CargoFareService;
use App\Services\DualControl\DualControlRegistry;
use App\Services\DualControlService;
use App\Support\ExcelDownload;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Cargo fares: the rate card, versions and every change to them.
 *
 * Changes are previewed (a plan with alerts), then submitted through dual control with the plan's
 * hash; if the fares change between preview and save, the save answers 409 with the new plan.
 */
class CargoFareController extends Controller
{
    use SubmitsDualControl;

    private const UNIT_LABEL = ['kg' => 'kg', 'cbm' => 'cbm', 'unit' => 'piece', 'lane_meter' => 'vehicle'];

    public function __construct(private CargoFareService $fares)
    {
    }

    // ── Reading ───────────────────────────────────────────────

    /**
     * Fares list. state: live (current + scheduled, default) | current | scheduled | history | all; at: prices at a moment.
     */
    public function index(Request $request)
    {
        $request->validate([
            'state' => 'nullable|in:live,current,scheduled,history,all',
            'at' => 'nullable|date',
            'cargo_type_id' => 'nullable|integer',
            'route_id' => 'nullable|integer',
        ]);

        $query = CargoFare::with(['cargoType', 'route', 'transportUnit', 'creator:id,first_name,last_name', 'ender:id,first_name,last_name'])
            ->withCount('items')
            ->when($request->filled('cargo_type_id'), fn ($q) => $q->where('cargo_type_id', $request->integer('cargo_type_id')))
            ->when($request->filled('route_id'), fn ($q) => $q->where('route_id', $request->integer('route_id')));

        if ($request->filled('at')) {
            $query->effectiveAt(Carbon::parse($request->at));
        } else {
            match ($request->input('state', 'live')) {
                'live' => $query->live(),
                'current' => $query->current(),
                'scheduled' => $query->scheduled(),
                'history' => $query->where(fn ($q) => $q->where('status', '!=', CargoFare::ACTIVE)->orWhere('effective_until', '<=', now())),
                'all' => null,
            };
        }

        $fares = $query->orderBy('cargo_type_id')->orderBy('route_id')->orderByRaw('transport_unit_id NULLS FIRST')->orderBy('effective_from')->get()
            ->each(fn (CargoFare $f) => $this->decorate($f));

        if ($request->boolean('missing_rate')) {
            $fares = $fares->where('missing_rate', true)->values();
        }

        return response()->json($fares);
    }

    /**
     * One row per cargo type, one column per route: the price in force at `at` (default now),
     * the next scheduled change, vessel-specific fares and any change awaiting approval.
     */
    public function rateCard(Request $request)
    {
        $request->validate(['at' => 'nullable|date', 'route_id' => 'nullable|integer']);
        $at = $request->filled('at') ? Carbon::parse($request->at) : now();

        $routes = Route::where('status', 'active')
            ->when($request->filled('route_id'), fn ($q) => $q->where('id', $request->integer('route_id')))
            ->orderBy('name')->get(['id', 'name', 'code']);
        $types = CargoType::where('status', 'active')->orderBy('category')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'code', 'category', 'measurement_unit', 'icon']);

        $inForce = CargoFare::with('transportUnit:id,name')->effectiveAt($at)->whereIn('route_id', $routes->pluck('id'))->get();
        $upcoming = CargoFare::where('status', CargoFare::ACTIVE)->where('effective_from', '>', $at)
            ->whereIn('route_id', $routes->pluck('id'))->orderBy('effective_from')->get();

        $cells = [];
        foreach ($types as $type) {
            foreach ($routes as $route) {
                $here = $inForce->where('cargo_type_id', $type->id)->where('route_id', $route->id);
                $fare = $here->firstWhere('transport_unit_id', null);
                $next = $upcoming->where('cargo_type_id', $type->id)->where('route_id', $route->id)->whereNull('transport_unit_id')->first();
                $open = DualControlRequest::openFor(CargoFare::seriesKey($type->id, $route->id, null));

                if (!$fare && !$next && $here->isEmpty() && !$open) {
                    continue;
                }
                $cells[$type->id][$route->id] = [
                    'fare' => $fare ? $this->cellFare($fare, $type) : null,
                    'next' => $next ? $this->cellFare($next, $type) : null,
                    'vessels' => $here->whereNotNull('transport_unit_id')->values()->map(fn ($f) => $this->cellFare($f, $type) + ['vessel' => $f->transportUnit?->name])->all(),
                    'open_change' => $open ? $this->openChange($open) : null,
                ];
            }
        }

        $groups = $types->groupBy('category')->map(fn ($items, $category) => [
            'category' => $category,
            'types' => $items->values()->map(fn ($t) => $t->only(['id', 'name', 'code', 'measurement_unit', 'icon']) + [
                'unit_label' => self::UNIT_LABEL[$t->measurement_unit] ?? $t->measurement_unit,
                'cells' => $cells[$t->id] ?? (object) [],
            ]),
        ])->values();

        $gaps = $routes->filter(fn ($r) => !$inForce->where('route_id', $r->id)->count())->pluck('name')->values();

        return response()->json([
            'at' => $at->toIso8601String(),
            'is_now' => !$request->filled('at'),
            'routes' => $routes,
            'groups' => $groups,
            'gaps' => $gaps,
            'unpriced_cells' => $types->count() * $routes->count() - $inForce->whereNull('transport_unit_id')->count(),
        ]);
    }

    /**
     * Every version for a cargo type on a route (all vessels), newest first — the side panel and the profile history.
     */
    public function series(Request $request)
    {
        $request->validate(['cargo_type_id' => 'required|integer', 'route_id' => 'nullable|integer']);

        $versions = CargoFare::withTrashed()
            ->with(['route:id,name,code', 'transportUnit:id,name', 'creator:id,first_name,last_name', 'ender:id,first_name,last_name', 'dualControlRequest:id,commence_at,executed_at,checker_id,status', 'dualControlRequest.checker:id,first_name,last_name', 'cargoType'])
            ->where('cargo_type_id', $request->integer('cargo_type_id'))
            ->when($request->filled('route_id'), fn ($q) => $q->where('route_id', $request->integer('route_id')))
            ->orderBy('route_id')->orderByRaw('transport_unit_id NULLS FIRST')->orderByDesc('effective_from')->orderByDesc('id')
            ->get();

        $usage = CargoItem::whereIn('fare_id', $versions->pluck('id'))->where('status', '!=', 'cancelled')
            ->groupBy('fare_id')->select('fare_id', DB::raw('count(*) as lines'), DB::raw('sum(final_fare) as revenue'))
            ->get()->keyBy('fare_id');

        return response()->json($versions->map(function (CargoFare $f) use ($usage) {
            $this->decorate($f);
            $f->setAttribute('lines', (int) ($usage[$f->id]->lines ?? 0));
            $f->setAttribute('revenue', (float) ($usage[$f->id]->revenue ?? 0));
            $f->setAttribute('deleted', $f->trashed());
            $f->setAttribute('requested_from', $f->dualControlRequest?->commence_at?->toIso8601String());
            $f->setAttribute('approved_by', $f->dualControlRequest?->checker_id && $f->dualControlRequest->checker_id !== $f->created_by ? $f->dualControlRequest->checker?->full_name : null);

            return $f;
        }));
    }

    /**
     * Whether each fare action needs approval right now (the forms word their alerts by this).
     */
    public function rules(DualControlService $dualControl)
    {
        return response()->json(collect(CargoFareService::OPERATIONS)->map(fn ($op) => [
            'operation' => $op,
            'requires_approval' => $dualControl->requiresDualControl($op),
            'timing' => DualControlRegistry::timing($op),
        ]));
    }

    // ── Changes ───────────────────────────────────────────────

    /**
     * Dry run of any change: kind = create | revise | end | delete | bulk | copy.
     */
    public function preview(Request $request)
    {
        $request->validate(['kind' => 'required|in:create,revise,end,delete,bulk,copy', 'fare_id' => 'nullable|integer']);
        [$intent, $startsAt] = $this->intent($request->kind, $request, $request->filled('fare_id') ? CargoFare::findOrFail($request->fare_id) : null);

        return response()->json($this->planResponse($this->fares->plan($request->kind, $intent, $startsAt)));
    }

    public function store(Request $request)
    {
        return $this->submit('create', $request);
    }

    public function revise(Request $request, CargoFare $cargoFare)
    {
        return $this->submit('revise', $request, $cargoFare);
    }

    public function end(Request $request, CargoFare $cargoFare)
    {
        return $this->submit('end', $request, $cargoFare);
    }

    public function destroy(Request $request, CargoFare $cargoFare)
    {
        return $this->submit('delete', $request, $cargoFare);
    }

    public function bulkValueCharge(Request $request)
    {
        return $this->submit('bulk', $request);
    }

    public function copy(Request $request)
    {
        return $this->submit('copy', $request);
    }

    public function export(Request $request)
    {
        $request->validate(['cargo_type_id' => 'nullable|integer', 'route_id' => 'nullable|integer']);

        $fares = CargoFare::withTrashed()
            ->with(['cargoType', 'route', 'transportUnit', 'creator', 'ender', 'dualControlRequest.checker'])
            ->when($request->filled('cargo_type_id'), fn ($q) => $q->where('cargo_type_id', $request->integer('cargo_type_id')))
            ->when($request->filled('route_id'), fn ($q) => $q->where('route_id', $request->integer('route_id')))
            ->orderBy('cargo_type_id')->orderBy('route_id')->orderByRaw('transport_unit_id NULLS FIRST')->orderBy('effective_from')
            ->get();
        $usage = CargoItem::whereIn('fare_id', $fares->pluck('id'))->where('status', '!=', 'cancelled')
            ->groupBy('fare_id')->select('fare_id', DB::raw('count(*) as lines'), DB::raw('sum(final_fare) as revenue'))
            ->get()->keyBy('fare_id');

        return ExcelDownload::make('Cargo_Fare_History_' . now()->format('Ymd_His') . '.xlsx', function ($writer) use ($fares, $usage) {
            foreach ($fares as $f) {
                $request = $f->dualControlRequest;
                $writer->addRow([
                    'Fare #' => $f->id,
                    'Version' => $f->version,
                    'Cargo Type' => $f->cargoType?->name,
                    'Code' => $f->cargoType?->code,
                    'Priced Per' => self::UNIT_LABEL[$f->cargoType?->measurement_unit] ?? $f->cargoType?->measurement_unit,
                    'Route' => $f->route?->name,
                    'Vessel' => $f->transportUnit?->name ?? 'All vessels',
                    'Flat TZS' => (float) $f->price_tzs,
                    'Flat USD' => (float) $f->price_usd,
                    'Per Kg TZS' => $f->price_per_kg_tzs === null ? null : (float) $f->price_per_kg_tzs,
                    'Per CBM TZS' => $f->price_per_cbm_tzs === null ? null : (float) $f->price_per_cbm_tzs,
                    'Min Charge TZS' => $f->min_charge_tzs === null ? null : (float) $f->min_charge_tzs,
                    'Value %' => (float) $f->value_charge_pct,
                    'Valid From' => $f->effective_from?->format('Y-m-d H:i'),
                    'Valid Until' => $f->effective_until?->format('Y-m-d H:i'),
                    'Requested Start' => $request?->commence_at?->format('Y-m-d H:i'),
                    'State' => $f->trashed() ? 'deleted' : $f->state,
                    'Created By' => $f->creator?->full_name,
                    'Approved By' => $request && $request->checker_id !== $request->maker_id ? $request->checker?->full_name : null,
                    'Ended By' => $f->ender?->full_name,
                    'Ended At' => $f->ended_at?->format('Y-m-d H:i'),
                    'Reason' => $f->change_reason,
                    'Previous Version #' => $f->previous_fare_id,
                    'Lines Priced' => (int) ($usage[$f->id]->lines ?? 0),
                    'Revenue TZS' => (float) ($usage[$f->id]->revenue ?? 0),
                ]);
            }
        });
    }

    // ── Pieces ────────────────────────────────────────────────

    private function submit(string $kind, Request $request, ?CargoFare $fare = null)
    {
        [$intent, $startsAt] = $this->intent($kind, $request, $fare);
        $plan = $this->fares->plan($kind, $intent, $startsAt);

        if ($request->filled('plan_hash') && $request->plan_hash !== $plan['hash']) {
            return response()->json(['message' => 'The fares changed while you were reviewing. Check the updated alerts and save again.', 'plan' => $this->planResponse($plan)], 409);
        }
        if ($plan['blocking']) {
            return response()->json(['message' => $plan['blocking'][0], 'plan' => $this->planResponse($plan)], 422);
        }
        if ($plan['replace_missing']) {
            return response()->json([
                'message' => 'Another fare covers these dates. Tick “Replace it” to confirm, or change the dates.',
                'errors' => ['replace' => ['Tick “Replace it” to confirm.']],
                'plan' => $this->planResponse($plan),
            ], 422);
        }

        $single = count($plan['series']) === 1 && in_array($kind, ['create', 'revise', 'end', 'delete'], true);
        $payload = [
            'before' => $fare ? array_merge($fare->prices(), [
                'version' => $fare->version,
                'effective_from' => $fare->effective_from?->toIso8601String(),
                'effective_until' => $fare->effective_until?->toIso8601String(),
            ]) : null,
            'after' => $this->fares->displayPayload($plan) + [
                'intent' => $intent + ['starts_at' => $startsAt?->toIso8601String()],
                'plan_hash' => $plan['hash'],
                'plan' => $this->planResponse($plan),
                'series_keys' => $this->fares->seriesKeys($plan),
            ],
        ];

        $messages = [
            'create' => 'Fare saved.', 'revise' => 'Fare changed — the previous version is kept in the history.',
            'end' => ($intent['revoke'] ?? false) ? 'Fare revoked.' : 'Fare end saved.', 'delete' => 'Fare deleted.',
            'bulk' => 'Value charge updated on ' . count(array_filter($plan['series'], fn ($s) => $s['actions'])) . ' fare(s).',
            'copy' => 'Fares copied to the route.',
        ];

        return $this->submitChange($request, CargoFareService::OPERATIONS[$kind], 'cargo_fare', $fare?->id ?? 0, $payload, $messages[$kind], null, [
            'subject_key' => $single ? $plan['series'][0]['key'] : null,
            'commence_at' => $kind === 'end' ? (!empty($intent['ends_at']) ? Carbon::parse($intent['ends_at']) : null) : $startsAt,
        ]);
    }

    /**
     * The request as an intent plus its start moment (null = immediately / when approved).
     *
     * @return array{0: array, 1: ?Carbon}
     */
    private function intent(string $kind, Request $request, ?CargoFare $fare): array
    {
        $operation = CargoFareService::OPERATIONS[$kind];

        switch ($kind) {
            case 'create':
            case 'revise':
                abort_if($kind === 'revise' && !$fare, 404);
                return [$this->fares->fareIntent($request->all(), $kind === 'revise' ? $fare : null), $this->takesEffect($request, $operation)];

            case 'end':
                abort_unless($fare, 404);
                $v = $request->validate([
                    'end_mode' => 'required|in:now,at',
                    'ends_on' => 'required_if:end_mode,at|nullable|date|after_or_equal:today',
                    'revoke' => 'boolean',
                    'reason' => 'required|string|min:5|max:255',
                ], [
                    'ends_on.required_if' => 'Choose the last day the fare applies.',
                    'reason.required' => 'Say why the fare is ending (kept with the fare history).',
                ]);
                $revoke = (bool) ($v['revoke'] ?? false);
                $endsAt = !$revoke && $v['end_mode'] === 'at' ? Carbon::parse($v['ends_on'])->startOfDay()->addDay() : null;

                return [['fare_id' => $fare->id, 'ends_at' => $endsAt?->toIso8601String(), 'revoke' => $revoke, 'reason' => $v['reason']], null];

            case 'delete':
                abort_unless($fare, 404);
                $v = $request->validate(['reason' => 'nullable|string|max:255']);

                return [['fare_id' => $fare->id, 'reason' => $v['reason'] ?? null], null];

            case 'bulk':
                $v = $request->validate([
                    'value_charge_pct' => 'required|numeric|min:0|max:100',
                    'cargo_type_id' => 'nullable|integer|exists:cargo_types,id',
                    'route_id' => 'nullable|integer|exists:routes,id',
                    'reason' => 'required|string|min:5|max:255',
                ], ['value_charge_pct.max' => 'The value charge is a percentage — enter 0 to 100.', 'reason.required' => 'Say why the value charge is changing.']);

                return [[
                    'value_charge_pct' => (float) $v['value_charge_pct'],
                    'cargo_type_id' => $v['cargo_type_id'] ?? null,
                    'route_id' => $v['route_id'] ?? null,
                    'reason' => $v['reason'],
                ], $this->takesEffect($request, $operation)];

            case 'copy':
                $v = $request->validate([
                    'source_route_id' => 'required|integer|exists:routes,id',
                    'target_route_id' => 'required|integer|exists:routes,id|different:source_route_id',
                    'type_ids' => 'nullable|array',
                    'type_ids.*' => 'integer|exists:cargo_types,id',
                    'adjust_pct' => 'nullable|numeric|min:-90|max:500',
                    'existing' => 'required|in:skip,replace',
                    'reason' => 'required|string|min:5|max:255',
                ], ['target_route_id.different' => 'Choose a different route to copy to.', 'reason.required' => 'Say why the fares are being copied.']);

                return [[
                    'source_route_id' => (int) $v['source_route_id'],
                    'target_route_id' => (int) $v['target_route_id'],
                    'type_ids' => array_map('intval', $v['type_ids'] ?? []) ?: null,
                    'adjust_pct' => (float) ($v['adjust_pct'] ?? 0),
                    'existing' => $v['existing'],
                    'reason' => $v['reason'],
                ], $this->takesEffect($request, $operation)];
        }

        abort(422);
    }

    /**
     * The plan without its internal actions — what the browser shows.
     */
    private function planResponse(array $plan): array
    {
        return [
            'operation' => $plan['operation'],
            'hash' => $plan['hash'],
            'starts_at' => $plan['starts_at'],
            'requires_approval' => $plan['requires_approval'],
            'blocking' => $plan['blocking'],
            'needs_replace' => $plan['needs_replace'],
            'replace_missing' => $plan['replace_missing'],
            'lines_kept' => $plan['lines_kept'],
            'series' => array_map(fn ($s) => [
                'key' => $s['key'],
                'cargo_type' => $s['type_name'],
                'route' => $s['route_name'],
                'vessel' => $s['unit_name'],
                'alerts' => $s['alerts'],
                'changes' => count($s['actions']),
                'skipped' => $s['skipped'] ?? false,
            ], $plan['series']),
        ];
    }

    private function decorate(CargoFare $f): CargoFare
    {
        $unit = $f->cargoType?->measurement_unit;
        $f->setAttribute('missing_rate', $f->basisRate($unit) <= 0);
        $f->setAttribute('price_label', $f->cargoType ? $this->fares->priceLabel($f->cargoType, $f->prices()) : null);
        $open = DualControlRequest::openFor($f->seriesKeyOf());
        $f->setAttribute('open_change', $open ? $this->openChange($open) : null);

        return $f;
    }

    private function cellFare(CargoFare $f, CargoType $type): array
    {
        return [
            'id' => $f->id,
            'version' => $f->version,
            'rate' => $f->basisRate($type->measurement_unit),
            'price_usd' => (float) $f->price_usd,
            'value_charge_pct' => (float) $f->value_charge_pct,
            'min_charge_tzs' => $f->min_charge_tzs === null ? null : (float) $f->min_charge_tzs,
            'from' => $f->effective_from?->toIso8601String(),
            'until' => $f->effective_until?->toIso8601String(),
            'state' => $f->state,
            'missing_rate' => $f->basisRate($type->measurement_unit) <= 0,
        ];
    }

    private function openChange(DualControlRequest $r): array
    {
        return [
            'id' => $r->id,
            'status' => $r->status,
            'label' => $r->label(),
            'takes_effect' => $r->commence_at?->toIso8601String(),
            'maker' => $r->maker?->full_name,
            'url' => route('admin.dual-control.show', $r),
        ];
    }
}
