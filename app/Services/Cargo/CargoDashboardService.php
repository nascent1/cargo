<?php

namespace App\Services\Cargo;

use App\Models\CargoFare;
use App\Models\CargoPayment;
use App\Models\Consignment;
use App\Models\DualControlRequest;
use App\Models\Route;
use App\Models\TransportService;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Figures for the admin cargo dashboard (summary) and the sales counter's own day (counter).
 * Money is TZS (bookings priced in USD are reported separately).
 * "Collected" means paid (by paid_at); "booked" means created in the period.
 */
class CargoDashboardService
{
    public const PERIODS = ['today' => 'Today', '7d' => 'Last 7 days', '30d' => 'Last 30 days', 'month' => 'This month'];

    /** Live shipment stages, in order */
    private const PIPELINE = ['pending_payment', 'booked', 'loaded', 'in_transit', 'delivered', 'unclaimed'];

    public function summary(string $period = 'today'): array
    {
        [$from, $to, $prevFrom, $prevTo] = $this->range($period);

        $now = $this->kpis($from, $to);
        $prev = $this->kpis($prevFrom, $prevTo);

        return [
            'period' => $period,
            'label' => self::PERIODS[$period] ?? self::PERIODS['today'],
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'kpis' => collect($now)->map(fn ($value, $key) => [
                'value' => $value,
                'previous' => $prev[$key],
                'change_pct' => $prev[$key] > 0 ? round(($value - $prev[$key]) / $prev[$key] * 100, 1) : null,
            ])->all(),
            'outstanding' => $this->outstanding(),
            'trend' => $this->trend($period, $from, $to),
            'by_channel' => $this->byChannel($from, $to),
            'top_routes' => $this->topRoutes($from, $to),
            'pipeline' => $this->pipeline($from, $to),
            'departures' => $this->departuresToday(),
            'attention' => $this->attention(),
            'recent' => $this->recent(),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * [from, to, previous from, previous to] — the previous window has the same length.
     */
    private function range(string $period): array
    {
        $now = CarbonImmutable::now();

        [$from, $to] = match ($period) {
            '7d' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            '30d' => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
            'month' => [$now->startOfMonth(), $now->endOfDay()],
            default => [$now->startOfDay(), $now->endOfDay()],
        };

        if ($period === 'month') {
            // Same days of last month (1st → today's day number)
            $prevFrom = $from->subMonthNoOverflow()->startOfMonth();
            $prevTo = $prevFrom->addDays($from->diffInDays($now->startOfDay()))->endOfDay();
        } else {
            $days = (int) $from->diffInDays($to->startOfDay()) + 1;
            $prevFrom = $from->subDays($days);
            $prevTo = $from->subSecond();
        }

        return [$from, $to, $prevFrom, $prevTo];
    }

    private function kpis(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $booked = Consignment::whereBetween('created_at', [$from, $to])
            ->selectRaw("COUNT(*) AS bookings,
                         COUNT(*) FILTER (WHERE status = 'cancelled') AS cancelled,
                         COALESCE(SUM(total_amount_tzs) FILTER (WHERE status <> 'cancelled'), 0) AS value,
                         COALESCE(SUM(total_weight_kg) FILTER (WHERE status <> 'cancelled'), 0) AS weight")
            ->first();

        $collected = (float) Consignment::where('payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$from, $to])
            ->sum('total_amount_tzs');

        $live = (int) $booked->bookings - (int) $booked->cancelled;

        return [
            'bookings' => (int) $booked->bookings,
            'collected' => $collected,
            'booked_value' => (float) $booked->value,
            'avg_value' => $live > 0 ? round((float) $booked->value / $live) : 0,
            'weight_kg' => round((float) $booked->weight, 1),
            'cancellation_rate' => (int) $booked->bookings > 0 ? round((int) $booked->cancelled / (int) $booked->bookings * 100, 1) : 0,
        ];
    }

    /**
     * Right now: not paid and not cancelled (includes bookings made before payments were tracked).
     */
    private function outstanding(): array
    {
        $row = Consignment::where('payment_status', '!=', 'paid')->where('status', '!=', 'cancelled')
            ->selectRaw("COUNT(*) AS count, COALESCE(SUM(total_amount_tzs), 0) AS value,
                         COUNT(*) FILTER (WHERE status = 'pending_payment') AS awaiting")
            ->first();

        return ['count' => (int) $row->count, 'value' => (float) $row->value, 'awaiting' => (int) $row->awaiting];
    }

    private function trend(string $period, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $hourly = $period === 'today';
        $bucket = $hourly ? "date_trunc('hour', %s)" : "date_trunc('day', %s)";

        $bookings = Consignment::whereBetween('created_at', [$from, $to])
            ->selectRaw(sprintf($bucket, 'created_at') . ' AS b, COUNT(*) AS n')
            ->groupBy('b')->pluck('n', 'b');

        $collected = Consignment::where('payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(paid_at, created_at)'), [$from, $to])
            ->selectRaw(sprintf($bucket, 'COALESCE(paid_at, created_at)') . ' AS b, SUM(total_amount_tzs) AS v')
            ->groupBy('b')->pluck('v', 'b');

        $labels = $counts = $amounts = [];
        $cursor = $from;
        $end = $hourly ? $from->setTime((int) now()->format('H'), 0) : $to->startOfDay();
        while ($cursor <= $end) {
            $key = $cursor->format('Y-m-d H:00:00');
            $labels[] = $hourly ? $cursor->format('H:00') : $cursor->format('d M');
            $counts[] = (int) ($bookings[$key] ?? 0);
            $amounts[] = (float) ($collected[$key] ?? 0);
            $cursor = $hourly ? $cursor->addHour() : $cursor->addDay();
        }

        return ['labels' => $labels, 'bookings' => $counts, 'collected' => $amounts];
    }

    private function byChannel(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Consignment::query()
            ->leftJoin('payment_methods as pm', 'pm.slug', '=', 'consignments.payment_method')
            ->where('consignments.payment_status', 'paid')
            ->whereBetween(DB::raw('COALESCE(consignments.paid_at, consignments.created_at)'), [$from, $to])
            ->groupBy('consignments.payment_method', 'pm.name')
            ->selectRaw("COALESCE(pm.name, consignments.payment_method, 'Unknown') AS label, COUNT(*) AS count, SUM(consignments.total_amount_tzs) AS value")
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count, 'value' => (float) $r->value])
            ->all();
    }

    private function topRoutes(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return Consignment::query()
            ->join('routes as r', 'r.id', '=', 'consignments.route_id')
            ->whereBetween('consignments.created_at', [$from, $to])
            ->where('consignments.status', '!=', 'cancelled')
            ->groupBy('r.id', 'r.name')
            ->selectRaw('r.name AS label, COUNT(*) AS count, SUM(consignments.total_amount_tzs) AS value, SUM(consignments.total_weight_kg) AS weight')
            ->orderByDesc('value')
            ->limit(6)
            ->get()
            ->map(fn ($r) => ['label' => $r->label, 'count' => (int) $r->count, 'value' => (float) $r->value, 'weight' => (float) $r->weight])
            ->all();
    }

    /**
     * Shipments currently at each live stage, plus collected in the period.
     */
    private function pipeline(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $live = Consignment::whereIn('status', self::PIPELINE)->groupBy('status')
            ->selectRaw('status, COUNT(*) AS n')->pluck('n', 'status');

        $stages = collect(self::PIPELINE)->map(fn ($s) => ['status' => $s, 'count' => (int) ($live[$s] ?? 0)]);
        $stages->push([
            'status' => 'collected',
            'count' => Consignment::where('status', 'collected')->whereBetween('collected_at', [$from, $to])->count(),
        ]);

        return $stages->all();
    }

    private function departuresToday(): array
    {
        $services = TransportService::with(['route', 'transportUnit', 'cargoInventory'])
            ->whereDate('departure_date', today())
            ->where('status', '!=', 'cancelled')
            ->orderBy('departure_time')
            ->get();

        $bookings = Consignment::whereIn('transport_service_id', $services->pluck('id'))
            ->where('status', '!=', 'cancelled')
            ->groupBy('transport_service_id')
            ->selectRaw('transport_service_id, COUNT(*) AS n, SUM(total_weight_kg) AS w')
            ->get()->keyBy('transport_service_id');

        return $services->map(function (TransportService $s) use ($bookings) {
            $inv = $s->cargoInventory;

            return [
                'id' => $s->id,
                'service_number' => $s->service_number,
                'route' => $s->route?->name,
                'unit' => $s->transportUnit?->name,
                'time' => $s->departure_time ? substr($s->departure_time, 0, 5) : null,
                'status' => $s->status,
                'bookings' => (int) ($bookings[$s->id]->n ?? 0),
                'weight_kg' => (float) ($bookings[$s->id]->w ?? 0),
                'utilisation' => $inv ? $this->utilisation($inv) : null,
            ];
        })->all();
    }

    /**
     * Used + held as a % of capacity, per dimension (null when the vessel has no capacity for it).
     */
    public function utilisation($inv): array
    {
        $pct = fn ($used, $held, $total) => (float) $total > 0 ? round(((float) $used + (float) $held) / (float) $total * 100) : null;

        return [
            'weight' => $pct($inv->used_weight_kg, $inv->held_weight_kg, $inv->total_weight_capacity_kg),
            'volume' => $pct($inv->used_volume_cbm, $inv->held_volume_cbm, $inv->total_volume_capacity_cbm),
            'lane' => $pct($inv->used_lane_meters, $inv->held_lane_meters, $inv->total_lane_meters),
        ];
    }

    private function attention(): array
    {
        $items = [];
        $weekAhead = [today()->toDateString(), today()->addDays(7)->toDateString()];

        $stalePayments = Consignment::where('status', 'pending_payment')->where('created_at', '<', now()->subMinutes(30))->count();
        $items[] = ['key' => 'awaiting_payment', 'severity' => 'warning', 'count' => $stalePayments,
            'label' => 'Awaiting payment for over 30 minutes', 'url' => route('admin.cargo.consignments', ['status' => 'pending_payment'])];

        $refunds = CargoPayment::where('status', 'refund_due')->count();
        $items[] = ['key' => 'refund_due', 'severity' => 'danger', 'count' => $refunds,
            'label' => 'Payments received that need a refund', 'url' => route('admin.reports.show', ['report' => 'collections', 'payment_status' => 'refund_due'])];

        $failed = CargoPayment::where('status', 'failed')->whereDate('updated_at', today())->count();
        $items[] = ['key' => 'failed_payments', 'severity' => 'warning', 'count' => $failed,
            'label' => 'Payment attempts failed today', 'url' => route('admin.reports.show', ['report' => 'collections', 'payment_status' => 'failed', 'date_from' => today()->toDateString()])];

        $uncollected = Consignment::whereIn('status', ['delivered', 'unclaimed'])->where('delivered_at', '<', now()->subDays(3))->count();
        $items[] = ['key' => 'uncollected', 'severity' => 'info', 'count' => $uncollected,
            'label' => 'Delivered but not collected for over 3 days', 'url' => route('admin.reports.show', ['report' => 'aging'])];

        $upcoming = TransportService::with('cargoInventory')
            ->whereBetween('departure_date', $weekAhead)->where('status', '!=', 'cancelled')->get();
        $full = $upcoming->filter(fn ($s) => $s->cargoInventory && max(array_filter($this->utilisation($s->cargoInventory), fn ($v) => $v !== null) ?: [0]) >= 85)->count();
        $items[] = ['key' => 'nearly_full', 'severity' => 'info', 'count' => $full,
            'label' => 'Departures in the next 7 days over 85% full', 'url' => route('admin.reports.show', ['report' => 'capacity', 'date_from' => $weekAhead[0], 'date_to' => $weekAhead[1]])];

        $noCapacity = $upcoming->filter(fn ($s) => !$s->cargoInventory
            || ((float) $s->cargoInventory->total_weight_capacity_kg <= 0 && (float) $s->cargoInventory->total_volume_capacity_cbm <= 0 && (float) $s->cargoInventory->total_lane_meters <= 0))->count();
        $items[] = ['key' => 'no_capacity', 'severity' => 'warning', 'count' => $noCapacity,
            'label' => 'Departures in the next 7 days with no capacity set', 'url' => route('admin.cargo.journeys.index')];

        // Fares in force now (older versions and ended fares don't price anything)
        $freeFares = DB::table('cargo_fares as f')->join('cargo_types as t', 't.id', '=', 'f.cargo_type_id')
            ->whereNull('f.deleted_at')->where('f.status', 'active')
            ->where('f.effective_from', '<=', now())->where(fn ($q) => $q->whereNull('f.effective_until')->orWhere('f.effective_until', '>', now()))
            ->where(fn ($q) => $q->where(fn ($q) => $q->where('t.measurement_unit', 'kg')->whereRaw('COALESCE(f.price_per_kg_tzs, 0) = 0'))
                ->orWhere(fn ($q) => $q->where('t.measurement_unit', 'cbm')->whereRaw('COALESCE(f.price_per_cbm_tzs, 0) = 0')))
            ->count();
        $items[] = ['key' => 'free_fares', 'severity' => 'danger', 'count' => $freeFares,
            'label' => 'Fares in force with no per-kg / per-cbm rate (not bookable)', 'url' => route('admin.cargo.fares', ['missing_rate' => 1])];

        $unpricedRoutes = Route::where('status', 'active')
            ->whereDoesntHave('cargoFares', fn ($q) => $q->effectiveAt(now()))
            ->count();
        $items[] = ['key' => 'unpriced_routes', 'severity' => 'danger', 'count' => $unpricedRoutes,
            'label' => 'Active routes with no cargo fares (nothing can be booked)', 'url' => route('admin.cargo.fares')];

        $fareChanges = CargoFare::where('status', CargoFare::ACTIVE)->whereBetween('effective_from', [now(), now()->addDays(7)])->count();
        $items[] = ['key' => 'fare_changes', 'severity' => 'info', 'count' => $fareChanges,
            'label' => 'Fare changes starting in the next 7 days', 'url' => route('admin.cargo.fares')];

        $user = auth()->user();
        if ($user && $user->hasPermission('dc:approve')) {
            $awaiting = DualControlRequest::where('status', 'pending')->where('maker_id', '!=', $user->id)->where('expires_at', '>', now())->count();
            $items[] = ['key' => 'dc_awaiting', 'severity' => 'warning', 'count' => $awaiting,
                'label' => 'Changes awaiting your approval', 'url' => route('admin.dual-control.index')];
        }
        $failedChanges = DualControlRequest::where('status', 'failed')->where('updated_at', '>=', now()->subDays(7))->count();
        $items[] = ['key' => 'dc_failed', 'severity' => 'danger', 'count' => $failedChanges,
            'label' => 'Scheduled changes that failed (last 7 days)', 'url' => route('admin.dual-control.index', ['tab' => 'all', 'status' => 'failed'])];

        return array_values(array_filter($items, fn ($i) => $i['count'] > 0));
    }

    private function recent(): array
    {
        return Consignment::with(['route:id,name', 'bookedByUser:id,first_name,last_name'])
            ->latest()->limit(10)->get()
            ->map(fn (Consignment $c) => [
                'id' => $c->id,
                'consignment_number' => $c->consignment_number,
                'sender_name' => $c->sender_name,
                'receiver_name' => $c->receiver_name,
                'route' => $c->route?->name,
                'status' => $c->status,
                'payment_status' => $c->payment_status,
                'amount' => $c->billing_amount,
                'currency' => $c->billing_currency,
                'booked_by' => $c->bookedByUser?->full_name,
                'created_at' => $c->created_at?->toIso8601String(),
            ])->all();
    }

    // ── Sales counter: the signed-in clerk's own day ──────────────

    public function counter(User $user): array
    {
        $today = CarbonImmutable::today();

        $made = fn (CarbonImmutable $day) => Consignment::where('booked_by_user_id', $user->id)
            ->whereBetween('created_at', [$day, $day->endOfDay()])
            ->where('status', '!=', 'cancelled')
            ->count();

        // Money this clerk took today: paid attempts they started (cash, push or Lipa Namba)
        $collected = CargoPayment::where('initiated_by_user_id', $user->id)
            ->where('status', 'paid')
            ->where('currency', 'TZS')
            ->whereBetween(DB::raw('COALESCE(confirmed_at, updated_at)'), [$today, $today->endOfDay()])
            ->selectRaw("COALESCE(SUM(amount), 0) AS total,
                         COALESCE(SUM(amount) FILTER (WHERE flow = 'cash'), 0) AS cash,
                         COUNT(*) AS payments")
            ->first();

        $awaiting = Consignment::where('booked_by_user_id', $user->id)->where('status', 'pending_payment')
            ->selectRaw('COUNT(*) AS count, COALESCE(SUM(total_amount_tzs), 0) AS value')
            ->first();

        return [
            'date' => $today->toDateString(),
            'tiles' => [
                'bookings' => ['today' => $made($today), 'yesterday' => $made($today->subDay())],
                'collected' => [
                    'total' => (float) $collected->total,
                    'cash' => (float) $collected->cash,
                    'mobile' => (float) $collected->total - (float) $collected->cash,
                    'payments' => (int) $collected->payments,
                ],
                'awaiting' => ['count' => (int) $awaiting->count, 'value' => (float) $awaiting->value],
            ],
            'departures' => $this->nextDepartures(),
            'action' => $this->awaitingPaymentFor($user),
            'recent' => $this->recentFor($user),
        ];
    }

    /**
     * Departures still open for booking today and tomorrow, with how full they are.
     */
    private function nextDepartures(): array
    {
        return TransportService::with(['route:id,name', 'transportUnit:id,name', 'cargoInventory'])
            ->availableForBooking()
            ->whereDate('departure_date', '<=', today()->addDay())
            ->orderBy('departure_date')->orderBy('departure_time')
            ->limit(10)
            ->get()
            ->map(fn (TransportService $s) => [
                'id' => $s->id,
                'date' => substr((string) $s->departure_date, 0, 10),
                'time' => substr((string) $s->departure_time, 0, 5),
                'route' => $s->route?->name,
                'vessel' => $s->transportUnit?->name,
                'space' => app(CargoInventoryService::class)->spaceSummary($s->cargoInventory)[1],
            ])->all();
    }

    /**
     * The clerk's bookings still waiting for money, with where their last payment attempt stands.
     */
    private function awaitingPaymentFor(User $user): array
    {
        return Consignment::with(['payments' => fn ($q) => $q->with('paymentMethod:id,name')->latest('id')])
            ->where('booked_by_user_id', $user->id)
            ->where('status', 'pending_payment')
            ->latest()->limit(6)->get()
            ->map(function (Consignment $c) {
                $last = $c->payments->first();
                $channel = $last?->paymentMethod?->name ?? 'Payment';
                $note = match (true) {
                    !$last => 'No payment started yet',
                    in_array($last->status, ['failed', 'expired', 'cancelled'], true) => "{$channel} payment " . str_replace('_', ' ', $last->status) . ' — try again or take cash',
                    $last->flow === 'lipa_namba' => 'Lipa Namba — waiting for the SMS code',
                    default => "{$channel} push sent — waiting for the customer",
                };

                return [
                    'id' => $c->id,
                    'consignment_number' => $c->consignment_number,
                    'amount' => (float) $c->billing_amount,
                    'currency' => $c->billing_currency,
                    'note' => $note,
                    'since' => $c->created_at?->toIso8601String(),
                ];
            })->all();
    }

    private function recentFor(User $user): array
    {
        return Consignment::with(['transportService:id,departure_date,departure_time,route_id', 'route:id,name'])
            ->where('booked_by_user_id', $user->id)
            ->latest()->limit(8)->get()
            ->map(fn (Consignment $c) => [
                'id' => $c->id,
                'consignment_number' => $c->consignment_number,
                'sender_name' => $c->sender_name,
                'receiver_name' => $c->receiver_name,
                'route' => $c->route?->name,
                'departure' => $c->transportService ? substr((string) $c->transportService->departure_date, 0, 10) . ' ' . substr((string) $c->transportService->departure_time, 0, 5) : null,
                'status' => $c->status,
                'payment_status' => $c->payment_status,
                'amount' => (float) $c->billing_amount,
                'currency' => $c->billing_currency,
            ])->all();
    }
}
