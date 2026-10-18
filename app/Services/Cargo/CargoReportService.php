<?php

namespace App\Services\Cargo;

use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cargo reports. Each report declares its filters and returns:
 *   summary  — headline cards [label, value, type]
 *   chart    — optional single-series bar chart [title, labels, values, type]
 *   sections — one or more tables [title, columns[key,label,type], rows, totals]
 * Column types: text, int, num, money, pct, date, datetime, status, payment, booking (link).
 * Money is TZS (total_amount_tzs / item fares in TZS).
 */
class CargoReportService
{
    /** Rows shown on screen; exports include everything */
    public const SCREEN_LIMIT = 500;

    public function definitions(): array
    {
        return [
            'sales' => [
                'title' => 'Sales summary',
                'icon' => 'fa-chart-column',
                'description' => 'Bookings, value booked, money collected and still outstanding — grouped by day, week, month, route, payment channel or staff member.',
                'filters' => ['dates', 'group_by', 'route_id', 'payment_method', 'booked_by'],
                'group_by' => ['day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'route' => 'Route', 'channel' => 'Payment channel', 'staff' => 'Booked by'],
            ],
            'register' => [
                'title' => 'Booking register',
                'icon' => 'fa-list',
                'description' => 'Every booking with sender, receiver, departure, status and payment — filter by booking or departure date.',
                'filters' => ['dates', 'date_field', 'route_id', 'status', 'payment_status', 'payment_method', 'booked_by'],
                'orientation' => 'landscape',
            ],
            'collections' => [
                'title' => 'Payment collections & cash-up',
                'icon' => 'fa-cash-register',
                'description' => 'Every payment attempt by channel and staff member, with references, plus cash-up totals per person.',
                'filters' => ['dates', 'attempt_status', 'payment_method', 'booked_by'],
                'orientation' => 'landscape',
            ],
            'capacity' => [
                'title' => 'Capacity utilisation',
                'icon' => 'fa-ship',
                'description' => 'Space used on each departure — weight, volume and lane metres against the vessel\'s capacity.',
                'filters' => ['dates', 'route_id', 'transport_unit_id'],
                'orientation' => 'landscape',
            ],
            'cargo_mix' => [
                'title' => 'Cargo mix',
                'icon' => 'fa-boxes-stacked',
                'description' => 'What is being shipped: lines, pieces, weight and revenue by cargo type or category.',
                'filters' => ['dates', 'group_by', 'route_id'],
                'group_by' => ['type' => 'Cargo type', 'category' => 'Category'],
            ],
            'cancellations' => [
                'title' => 'Cancellations',
                'icon' => 'fa-ban',
                'description' => 'Cancelled bookings with reasons, value, and whether they were cancelled by staff or automatically for non-payment.',
                'filters' => ['dates', 'route_id', 'booked_by'],
                'orientation' => 'landscape',
            ],
            'aging' => [
                'title' => 'Uncollected cargo',
                'icon' => 'fa-hourglass-half',
                'description' => 'Cargo that has arrived but not been collected, grouped by how long it has been waiting.',
                'filters' => ['route_id'],
            ],
        ];
    }

    public function defaultFilters(string $report): array
    {
        return match ($report) {
            'capacity' => ['date_from' => today()->subDays(7)->toDateString(), 'date_to' => today()->addDays(7)->toDateString()],
            'sales' => ['date_from' => today()->startOfMonth()->toDateString(), 'date_to' => today()->toDateString(), 'group_by' => 'day'],
            'cargo_mix' => ['date_from' => today()->startOfMonth()->toDateString(), 'date_to' => today()->toDateString(), 'group_by' => 'type'],
            default => ['date_from' => today()->startOfMonth()->toDateString(), 'date_to' => today()->toDateString()],
        };
    }

    public function run(string $report, array $filters, bool $screen = false): array
    {
        return match ($report) {
            'sales' => $this->sales($filters),
            'register' => $this->register($filters, $screen ? self::SCREEN_LIMIT : null),
            'collections' => $this->collections($filters),
            'capacity' => $this->capacity($filters),
            'cargo_mix' => $this->cargoMix($filters),
            'cancellations' => $this->cancellations($filters),
            'aging' => $this->aging($filters),
        };
    }

    // ── 1. Sales summary ─────────────────────────────────────

    private function sales(array $f): array
    {
        $groups = [
            'day' => ["to_char(c.created_at, 'YYYY-MM-DD')", true],
            'week' => ["to_char(date_trunc('week', c.created_at), 'YYYY-MM-DD')", true],
            'month' => ["to_char(c.created_at, 'YYYY-MM')", true],
            'route' => ["COALESCE(r.name, 'Unknown route')", false],
            'channel' => ["COALESCE(pm.name, c.payment_method, 'Not recorded')", false],
            'staff' => ["COALESCE(NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), ''), 'Unknown')", false],
        ];
        $groupBy = array_key_exists($f['group_by'] ?? '', $groups) ? $f['group_by'] : 'day';
        [$expr, $isTime] = $groups[$groupBy];

        $rows = $this->consignments($f)
            ->leftJoin('routes as r', 'r.id', '=', 'c.route_id')
            ->leftJoin('payment_methods as pm', 'pm.slug', '=', 'c.payment_method')
            ->leftJoin('users as u', 'u.id', '=', 'c.booked_by_user_id')
            ->leftJoinSub($this->itemValueCharges(), 'iv', 'iv.consignment_id', '=', 'c.id')
            ->selectRaw("{$expr} AS label,
                COUNT(*) AS bookings,
                COUNT(*) FILTER (WHERE c.status = 'cancelled') AS cancelled,
                COALESCE(SUM(c.total_amount_tzs) FILTER (WHERE c.status <> 'cancelled'), 0) AS booked_value,
                COALESCE(SUM(c.total_amount_tzs) FILTER (WHERE c.status <> 'cancelled' AND c.payment_status = 'paid'), 0) AS collected,
                COALESCE(SUM(c.total_amount_tzs) FILTER (WHERE c.status <> 'cancelled' AND c.payment_status <> 'paid'), 0) AS outstanding,
                COALESCE(SUM(c.discount_amount) FILTER (WHERE c.status <> 'cancelled'), 0) AS discounts,
                COALESCE(SUM(iv.value_charge) FILTER (WHERE c.status <> 'cancelled'), 0) AS value_charges,
                COALESCE(SUM(c.total_weight_kg) FILTER (WHERE c.status <> 'cancelled'), 0) AS weight")
            ->groupByRaw($expr)
            ->when($isTime, fn ($q) => $q->orderByRaw($expr), fn ($q) => $q->orderByDesc('booked_value'))
            ->get()
            ->map(function ($r) use ($groupBy) {
                $r = (array) $r;
                $r['label'] = match ($groupBy) {
                    'day' => Carbon::parse($r['label'])->format('D d M Y'),
                    'week' => 'Week of ' . Carbon::parse($r['label'])->format('d M Y'),
                    'month' => Carbon::parse($r['label'] . '-01')->format('F Y'),
                    default => $r['label'],
                };

                return $r;
            })->all();

        $totals = $this->sum($rows, ['bookings', 'cancelled', 'booked_value', 'collected', 'outstanding', 'discounts', 'value_charges', 'weight']);
        $groupLabel = $this->definitions()['sales']['group_by'][$groupBy];

        return [
            'summary' => [
                ['label' => 'Bookings', 'value' => $totals['bookings'], 'type' => 'int'],
                ['label' => 'Value booked', 'value' => $totals['booked_value'], 'type' => 'money'],
                ['label' => 'Collected', 'value' => $totals['collected'], 'type' => 'money'],
                ['label' => 'Outstanding', 'value' => $totals['outstanding'], 'type' => 'money'],
                ['label' => 'Value charges', 'value' => $totals['value_charges'], 'type' => 'money'],
                ['label' => 'Cancelled', 'value' => $totals['cancelled'], 'type' => 'int'],
            ],
            'chart' => [
                'title' => 'Value booked by ' . strtolower($groupLabel) . ' (TZS)',
                'labels' => array_column($rows, 'label'),
                'values' => array_map('floatval', array_column($rows, 'booked_value')),
                'type' => 'money',
            ],
            'sections' => [[
                'title' => 'By ' . strtolower($groupLabel),
                'columns' => [
                    ['key' => 'label', 'label' => $groupLabel, 'type' => 'text'],
                    ['key' => 'bookings', 'label' => 'Bookings', 'type' => 'int'],
                    ['key' => 'cancelled', 'label' => 'Cancelled', 'type' => 'int'],
                    ['key' => 'weight', 'label' => 'Weight (kg)', 'type' => 'num'],
                    ['key' => 'booked_value', 'label' => 'Value booked', 'type' => 'money'],
                    ['key' => 'discounts', 'label' => 'Discounts', 'type' => 'money'],
                    ['key' => 'value_charges', 'label' => 'Value charges', 'type' => 'money'],
                    ['key' => 'collected', 'label' => 'Collected', 'type' => 'money'],
                    ['key' => 'outstanding', 'label' => 'Outstanding', 'type' => 'money'],
                ],
                'rows' => $rows,
                'totals' => ['label' => 'Total'] + $totals,
            ]],
            'notes' => ['Grouped by booking date. "Collected" counts paid bookings; "Outstanding" counts unpaid bookings that are not cancelled. "Value charges" is the part of the value booked charged as a percentage of declared value.'],
        ];
    }

    // ── 2. Booking register ──────────────────────────────────

    private function register(array $f, ?int $limit = null): array
    {
        $base = $this->consignments($f, ($f['date_field'] ?? '') === 'departure' ? 'ts.departure_date' : 'c.created_at')
            ->leftJoin('routes as r', 'r.id', '=', 'c.route_id')
            ->leftJoin('transport_units as tu', 'tu.id', '=', 'ts.transport_unit_id')
            ->leftJoin('payment_methods as pm', 'pm.slug', '=', 'c.payment_method')
            ->leftJoin('users as u', 'u.id', '=', 'c.booked_by_user_id')
            ->leftJoinSub($this->itemValueCharges(), 'iv', 'iv.consignment_id', '=', 'c.id');

        $totals = (clone $base)->selectRaw('COUNT(*) AS n, COALESCE(SUM(c.total_weight_kg), 0) AS weight,
            COALESCE(SUM(c.total_amount_tzs) FILTER (WHERE c.status <> \'cancelled\'), 0) AS amount,
            COALESCE(SUM(iv.value_charge) FILTER (WHERE c.status <> \'cancelled\'), 0) AS value_charge,
            COALESCE(SUM(c.total_amount_tzs) FILTER (WHERE c.status <> \'cancelled\' AND c.payment_status = \'paid\'), 0) AS paid')->first();

        $rows = $base->selectRaw("c.id, c.consignment_number, c.created_at, ts.departure_date, ts.departure_time, r.name AS route, tu.name AS vessel,
                c.sender_name, c.sender_phone, c.receiver_name, c.receiver_phone,
                (SELECT COALESCE(SUM(GREATEST(i.quantity, 1)), 0) FROM cargo_items i WHERE i.consignment_id = c.id) AS pieces,
                c.total_weight_kg AS weight, c.status, c.payment_status, COALESCE(pm.name, c.payment_method) AS channel,
                c.total_amount_tzs AS amount, c.discount_amount AS discount, COALESCE(iv.value_charge, 0) AS value_charge,
                NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '') AS booked_by")
            ->orderByDesc('c.created_at')
            ->when($limit, fn ($q) => $q->limit($limit))
            ->get()
            ->map(fn ($r) => array_merge((array) $r, [
                'departure' => trim(($r->departure_date ?? '') . ' ' . substr($r->departure_time ?? '', 0, 5)),
            ]))->all();

        return [
            'summary' => [
                ['label' => 'Bookings', 'value' => (int) $totals->n, 'type' => 'int'],
                ['label' => 'Weight', 'value' => (float) $totals->weight, 'type' => 'kg'],
                ['label' => 'Value (excl. cancelled)', 'value' => (float) $totals->amount, 'type' => 'money'],
                ['label' => 'Paid', 'value' => (float) $totals->paid, 'type' => 'money'],
            ],
            'chart' => null,
            'total_rows' => (int) $totals->n,
            'sections' => [[
                'title' => 'Bookings',
                'columns' => [
                    ['key' => 'consignment_number', 'label' => 'Booking', 'type' => 'booking'],
                    ['key' => 'created_at', 'label' => 'Booked', 'type' => 'datetime'],
                    ['key' => 'departure', 'label' => 'Departure', 'type' => 'text'],
                    ['key' => 'route', 'label' => 'Route', 'type' => 'text'],
                    ['key' => 'vessel', 'label' => 'Vessel', 'type' => 'text'],
                    ['key' => 'sender_name', 'label' => 'Sender', 'type' => 'text'],
                    ['key' => 'sender_phone', 'label' => 'Sender phone', 'type' => 'text'],
                    ['key' => 'receiver_name', 'label' => 'Receiver', 'type' => 'text'],
                    ['key' => 'receiver_phone', 'label' => 'Receiver phone', 'type' => 'text'],
                    ['key' => 'pieces', 'label' => 'Pieces', 'type' => 'int'],
                    ['key' => 'weight', 'label' => 'Weight (kg)', 'type' => 'num'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'payment_status', 'label' => 'Payment', 'type' => 'payment'],
                    ['key' => 'channel', 'label' => 'Channel', 'type' => 'text'],
                    ['key' => 'discount', 'label' => 'Discount', 'type' => 'money'],
                    ['key' => 'value_charge', 'label' => 'Value charge', 'type' => 'money'],
                    ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
                    ['key' => 'booked_by', 'label' => 'Booked by', 'type' => 'text'],
                ],
                'rows' => $rows,
                'totals' => ['consignment_number' => 'Total', 'weight' => (float) $totals->weight, 'value_charge' => (float) $totals->value_charge, 'amount' => (float) $totals->amount],
            ]],
            'notes' => ['The amount total leaves out cancelled bookings.'],
        ];
    }

    // ── 3. Payment collections & cash-up ─────────────────────

    private function collections(array $f): array
    {
        $staffExpr = "NULLIF(TRIM(CONCAT(COALESCE(cu.first_name, iu.first_name), ' ', COALESCE(cu.last_name, iu.last_name))), '')";

        $base = DB::table('cargo_payments as p')
            ->join('consignments as c', 'c.id', '=', 'p.consignment_id')
            ->leftJoin('payment_methods as pm', 'pm.id', '=', 'p.payment_method_id')
            ->leftJoin('users as iu', 'iu.id', '=', 'p.initiated_by_user_id')
            ->leftJoin('users as cu', 'cu.id', '=', 'p.confirmed_by_user_id')
            ->when($f['date_from'] ?? null, fn ($q, $d) => $q->whereDate(DB::raw('COALESCE(p.confirmed_at, p.created_at)'), '>=', $d))
            ->when($f['date_to'] ?? null, fn ($q, $d) => $q->whereDate(DB::raw('COALESCE(p.confirmed_at, p.created_at)'), '<=', $d))
            ->when($f['payment_status'] ?? null, fn ($q, $s) => $q->where('p.status', $s))
            ->when($f['payment_method'] ?? null, fn ($q, $m) => $q->where('pm.slug', $m))
            ->when($f['booked_by'] ?? null, fn ($q, $u) => $q->where(fn ($q) => $q->where('p.confirmed_by_user_id', $u)->orWhere('p.initiated_by_user_id', $u)));

        $rows = (clone $base)
            ->selectRaw("p.reference, c.id, c.consignment_number, p.created_at, p.confirmed_at, pm.name AS channel, p.flow,
                p.payer_msisdn AS payer, p.amount, p.status, COALESCE(p.manual_reference, p.provider_reference) AS provider_ref,
                p.failure_reason, {$staffExpr} AS staff")
            ->orderByDesc(DB::raw('COALESCE(p.confirmed_at, p.created_at)'))
            ->get()->map(fn ($r) => (array) $r)->all();

        $paid = collect($rows)->where('status', 'paid');

        $cashUp = (clone $base)->where('p.status', 'paid')
            ->selectRaw("COALESCE({$staffExpr}, 'Unknown') AS staff, COALESCE(pm.name, 'Unknown') AS channel, COUNT(*) AS payments, SUM(p.amount) AS amount")
            ->groupByRaw("COALESCE({$staffExpr}, 'Unknown'), COALESCE(pm.name, 'Unknown')")
            ->orderByRaw('1, 2')
            ->get()->map(fn ($r) => (array) $r)->all();

        $byChannel = $paid->groupBy('channel')->map(fn ($g, $k) => ['label' => $k ?: 'Unknown', 'value' => (float) $g->sum('amount')])->sortByDesc('value')->values();

        return [
            'summary' => [
                ['label' => 'Collected', 'value' => (float) $paid->sum('amount'), 'type' => 'money'],
                ['label' => 'Paid payments', 'value' => $paid->count(), 'type' => 'int'],
                ['label' => 'Failed / expired', 'value' => collect($rows)->whereIn('status', ['failed', 'expired'])->count(), 'type' => 'int'],
                ['label' => 'Refunds due', 'value' => collect($rows)->where('status', 'refund_due')->count(), 'type' => 'int'],
            ],
            'chart' => $byChannel->isEmpty() ? null : [
                'title' => 'Collected by channel (TZS)',
                'labels' => $byChannel->pluck('label')->all(),
                'values' => $byChannel->pluck('value')->all(),
                'type' => 'money',
            ],
            'sections' => [
                [
                    'title' => 'Cash-up by staff and channel (paid only)',
                    'columns' => [
                        ['key' => 'staff', 'label' => 'Staff member', 'type' => 'text'],
                        ['key' => 'channel', 'label' => 'Channel', 'type' => 'text'],
                        ['key' => 'payments', 'label' => 'Payments', 'type' => 'int'],
                        ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
                    ],
                    'rows' => $cashUp,
                    'totals' => ['staff' => 'Total', 'payments' => array_sum(array_column($cashUp, 'payments')), 'amount' => array_sum(array_column($cashUp, 'amount'))],
                ],
                [
                    'title' => 'Payment attempts',
                    'columns' => [
                        ['key' => 'reference', 'label' => 'Reference', 'type' => 'text'],
                        ['key' => 'consignment_number', 'label' => 'Booking', 'type' => 'booking'],
                        ['key' => 'created_at', 'label' => 'Started', 'type' => 'datetime'],
                        ['key' => 'confirmed_at', 'label' => 'Confirmed', 'type' => 'datetime'],
                        ['key' => 'channel', 'label' => 'Channel', 'type' => 'text'],
                        ['key' => 'payer', 'label' => 'Payer', 'type' => 'text'],
                        ['key' => 'provider_ref', 'label' => 'Provider / SMS ref', 'type' => 'text'],
                        ['key' => 'status', 'label' => 'Status', 'type' => 'attempt'],
                        ['key' => 'failure_reason', 'label' => 'Note', 'type' => 'text'],
                        ['key' => 'staff', 'label' => 'Staff', 'type' => 'text'],
                        ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
                    ],
                    'rows' => $rows,
                    'totals' => null,
                ],
            ],
            'notes' => ['Dates use the confirmation time for paid attempts and the start time otherwise. Cash-up counts only paid attempts, credited to whoever confirmed (or started) them.'],
        ];
    }

    // ── 4. Capacity utilisation ──────────────────────────────

    private function capacity(array $f): array
    {
        $rows = DB::table('transport_services as ts')
            ->join('cargo_inventories as inv', 'inv.transport_service_id', '=', 'ts.id')
            ->leftJoin('routes as r', 'r.id', '=', 'ts.route_id')
            ->leftJoin('transport_units as tu', 'tu.id', '=', 'ts.transport_unit_id')
            ->where('ts.status', '!=', 'cancelled')
            ->when($f['date_from'] ?? null, fn ($q, $d) => $q->whereDate('ts.departure_date', '>=', $d))
            ->when($f['date_to'] ?? null, fn ($q, $d) => $q->whereDate('ts.departure_date', '<=', $d))
            ->when($f['route_id'] ?? null, fn ($q, $id) => $q->where('ts.route_id', $id))
            ->when($f['transport_unit_id'] ?? null, fn ($q, $id) => $q->where('ts.transport_unit_id', $id))
            ->selectRaw("ts.id, ts.departure_date, ts.departure_time, r.name AS route, tu.name AS vessel,
                (SELECT COUNT(*) FROM consignments c WHERE c.transport_service_id = ts.id AND c.status <> 'cancelled' AND c.deleted_at IS NULL) AS bookings,
                inv.used_weight_kg + inv.held_weight_kg AS weight_used, inv.total_weight_capacity_kg AS weight_total,
                inv.used_volume_cbm + inv.held_volume_cbm AS volume_used, inv.total_volume_capacity_cbm AS volume_total,
                inv.used_lane_meters + inv.held_lane_meters AS lane_used, inv.total_lane_meters AS lane_total")
            ->orderBy('ts.departure_date')->orderBy('ts.departure_time')
            ->get()
            ->map(function ($r) {
                $r = (array) $r;
                $pct = fn ($u, $t) => (float) $t > 0 ? round((float) $u / (float) $t * 100, 1) : null;
                $r['departure'] = $r['departure_date'] . ' ' . substr($r['departure_time'] ?? '', 0, 5);
                $r['weight_pct'] = $pct($r['weight_used'], $r['weight_total']);
                $r['volume_pct'] = $pct($r['volume_used'], $r['volume_total']);
                $r['lane_pct'] = $pct($r['lane_used'], $r['lane_total']);
                $r['peak_pct'] = max(array_filter([$r['weight_pct'], $r['volume_pct'], $r['lane_pct']], fn ($v) => $v !== null) ?: [0]);

                return $r;
            })->all();

        $withLoad = collect($rows)->filter(fn ($r) => $r['bookings'] > 0);
        $avg = fn ($key) => round((float) collect($rows)->whereNotNull($key)->avg($key), 1);

        return [
            'summary' => [
                ['label' => 'Departures', 'value' => count($rows), 'type' => 'int'],
                ['label' => 'With cargo booked', 'value' => $withLoad->count(), 'type' => 'int'],
                ['label' => 'Avg weight used', 'value' => $avg('weight_pct'), 'type' => 'pct'],
                ['label' => 'Over 85% full', 'value' => collect($rows)->where('peak_pct', '>=', 85)->count(), 'type' => 'int'],
            ],
            'chart' => null,
            'sections' => [[
                'title' => 'Departures',
                'columns' => [
                    ['key' => 'departure', 'label' => 'Departure', 'type' => 'text'],
                    ['key' => 'route', 'label' => 'Route', 'type' => 'text'],
                    ['key' => 'vessel', 'label' => 'Vessel', 'type' => 'text'],
                    ['key' => 'bookings', 'label' => 'Bookings', 'type' => 'int'],
                    ['key' => 'weight_used', 'label' => 'Weight used (kg)', 'type' => 'num'],
                    ['key' => 'weight_total', 'label' => 'Weight capacity', 'type' => 'num'],
                    ['key' => 'weight_pct', 'label' => 'Weight %', 'type' => 'pct'],
                    ['key' => 'volume_pct', 'label' => 'Volume %', 'type' => 'pct'],
                    ['key' => 'lane_pct', 'label' => 'Lane m %', 'type' => 'pct'],
                ],
                'rows' => $rows,
                'totals' => null,
            ]],
            'notes' => ['Space used includes active holds. A blank % means the vessel has no capacity set for that measure.'],
        ];
    }

    // ── 5. Cargo mix ─────────────────────────────────────────

    private function cargoMix(array $f): array
    {
        $byCategory = ($f['group_by'] ?? 'type') === 'category';
        $label = $byCategory ? 'INITCAP(ct.category)' : 'ct.name';

        $rows = $this->consignments($f)
            ->join('cargo_items as i', 'i.consignment_id', '=', 'c.id')
            ->join('cargo_types as ct', 'ct.id', '=', 'i.cargo_type_id')
            ->where('c.status', '!=', 'cancelled')
            ->selectRaw("{$label} AS label, " . ($byCategory ? "'' AS unit" : 'ct.measurement_unit AS unit') . ",
                COUNT(*) AS lines, COALESCE(SUM(GREATEST(i.quantity, 1)), 0) AS pieces,
                COALESCE(SUM(i.weight_kg), 0) AS weight, COALESCE(SUM(i.volume_cbm), 0) AS volume,
                COALESCE(SUM(i.final_fare), 0) AS revenue")
            ->groupByRaw($byCategory ? 'INITCAP(ct.category)' : 'ct.name, ct.measurement_unit')
            ->orderByDesc('revenue')
            ->get()->map(fn ($r) => (array) $r)->all();

        $totals = $this->sum($rows, ['lines', 'pieces', 'weight', 'volume', 'revenue']);
        $top = array_slice($rows, 0, 10);
        $columns = [['key' => 'label', 'label' => $byCategory ? 'Category' : 'Cargo type', 'type' => 'text']];
        if (!$byCategory) {
            $columns[] = ['key' => 'unit', 'label' => 'Priced per', 'type' => 'text'];
        }

        return [
            'summary' => [
                ['label' => 'Cargo lines', 'value' => $totals['lines'], 'type' => 'int'],
                ['label' => 'Pieces', 'value' => $totals['pieces'], 'type' => 'int'],
                ['label' => 'Weight', 'value' => $totals['weight'], 'type' => 'kg'],
                ['label' => 'Revenue', 'value' => $totals['revenue'], 'type' => 'money'],
            ],
            'chart' => [
                'title' => 'Revenue by ' . ($byCategory ? 'category' : 'cargo type') . (count($rows) > 10 ? ' (top 10)' : '') . ' (TZS)',
                'labels' => array_column($top, 'label'),
                'values' => array_map('floatval', array_column($top, 'revenue')),
                'type' => 'money',
                'horizontal' => true,
            ],
            'sections' => [[
                'title' => $byCategory ? 'By category' : 'By cargo type',
                'columns' => array_merge($columns, [
                    ['key' => 'lines', 'label' => 'Lines', 'type' => 'int'],
                    ['key' => 'pieces', 'label' => 'Pieces', 'type' => 'int'],
                    ['key' => 'weight', 'label' => 'Weight (kg)', 'type' => 'num'],
                    ['key' => 'volume', 'label' => 'Volume (cbm)', 'type' => 'num'],
                    ['key' => 'revenue', 'label' => 'Revenue', 'type' => 'money'],
                ]),
                'rows' => $rows,
                'totals' => ['label' => 'Total'] + $totals,
            ]],
            'notes' => ['Booking date range; cancelled bookings are left out. Revenue is the fare on each line after any discount.'],
        ];
    }

    // ── 6. Cancellations ─────────────────────────────────────

    private function cancellations(array $f): array
    {
        $f['status'] = 'cancelled';
        $autoReason = "c.cancellation_reason ILIKE 'Payment not received in time%'";

        $rows = $this->consignments($f, 'c.cancelled_at')
            ->leftJoin('routes as r', 'r.id', '=', 'c.route_id')
            ->leftJoin('users as u', 'u.id', '=', 'c.booked_by_user_id')
            ->selectRaw("c.id, c.consignment_number, c.created_at, c.cancelled_at, r.name AS route, c.sender_name,
                c.total_amount_tzs AS amount, c.payment_status, c.cancellation_reason AS reason,
                CASE WHEN {$autoReason} THEN 'Automatic (unpaid)' ELSE 'Staff' END AS cancelled_by_type,
                (SELECT NULLIF(TRIM(CONCAT(cu.first_name, ' ', cu.last_name)), '') FROM tracking_events te
                    LEFT JOIN users cu ON cu.id = te.performed_by_id
                  WHERE te.consignment_id = c.id AND te.to_status = 'cancelled' AND te.cargo_item_id IS NULL
                  ORDER BY te.id DESC LIMIT 1) AS cancelled_by,
                NULLIF(TRIM(CONCAT(u.first_name, ' ', u.last_name)), '') AS booked_by")
            ->orderByDesc('c.cancelled_at')
            ->get()->map(fn ($r) => (array) $r)->all();

        $rows = array_map(function ($r) {
            if ($r['cancelled_by_type'] !== 'Staff') {
                $r['cancelled_by'] = 'System';
            }

            return $r;
        }, $rows);

        $all = collect($rows);
        $auto = $all->where('cancelled_by_type', '!=', 'Staff');

        return [
            'summary' => [
                ['label' => 'Cancelled', 'value' => $all->count(), 'type' => 'int'],
                ['label' => 'Value', 'value' => (float) $all->sum('amount'), 'type' => 'money'],
                ['label' => 'Automatic (unpaid)', 'value' => $auto->count(), 'type' => 'int'],
                ['label' => 'Were paid (refund)', 'value' => $all->where('payment_status', 'paid')->count(), 'type' => 'int'],
            ],
            'chart' => null,
            'sections' => [[
                'title' => 'Cancelled bookings',
                'columns' => [
                    ['key' => 'consignment_number', 'label' => 'Booking', 'type' => 'booking'],
                    ['key' => 'created_at', 'label' => 'Booked', 'type' => 'datetime'],
                    ['key' => 'cancelled_at', 'label' => 'Cancelled', 'type' => 'datetime'],
                    ['key' => 'route', 'label' => 'Route', 'type' => 'text'],
                    ['key' => 'sender_name', 'label' => 'Sender', 'type' => 'text'],
                    ['key' => 'cancelled_by_type', 'label' => 'Type', 'type' => 'text'],
                    ['key' => 'cancelled_by', 'label' => 'Cancelled by', 'type' => 'text'],
                    ['key' => 'reason', 'label' => 'Reason', 'type' => 'text'],
                    ['key' => 'payment_status', 'label' => 'Payment', 'type' => 'payment'],
                    ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
                ],
                'rows' => $rows,
                'totals' => ['consignment_number' => 'Total', 'amount' => (float) $all->sum('amount')],
            ]],
            'notes' => ['Filtered by cancellation date. Paid bookings that were cancelled need a refund.'],
        ];
    }

    // ── 7. Uncollected cargo aging ───────────────────────────

    private function aging(array $f): array
    {
        $rows = DB::table('consignments as c')
            ->leftJoin('routes as r', 'r.id', '=', 'c.route_id')
            ->whereNull('c.deleted_at')
            ->whereIn('c.status', ['delivered', 'unclaimed'])
            ->when($f['route_id'] ?? null, fn ($q, $id) => $q->where('c.route_id', $id))
            ->selectRaw("c.id, c.consignment_number, c.receiver_name, c.receiver_phone, r.name AS route, c.status,
                COALESCE(c.delivered_at, c.updated_at) AS arrived_at, c.total_amount_tzs AS amount,
                (CURRENT_DATE - COALESCE(c.delivered_at, c.updated_at)::date) AS days")
            ->orderByDesc('days')
            ->get()
            ->map(function ($r) {
                $r = (array) $r;
                $d = (int) $r['days'];
                $r['bucket'] = $d <= 3 ? '0–3 days' : ($d <= 7 ? '4–7 days' : ($d <= 14 ? '8–14 days' : '15+ days'));

                return $r;
            })->all();

        $buckets = collect(['0–3 days', '4–7 days', '8–14 days', '15+ days'])
            ->mapWithKeys(fn ($b) => [$b => collect($rows)->where('bucket', $b)->count()]);

        return [
            'summary' => $buckets->map(fn ($n, $b) => ['label' => "Waiting {$b}", 'value' => $n, 'type' => 'int'])->values()->all(),
            'chart' => [
                'title' => 'Uncollected bookings by days waiting',
                'labels' => $buckets->keys()->all(),
                'values' => $buckets->values()->all(),
                'type' => 'int',
            ],
            'sections' => [[
                'title' => 'Waiting for collection',
                'columns' => [
                    ['key' => 'consignment_number', 'label' => 'Booking', 'type' => 'booking'],
                    ['key' => 'receiver_name', 'label' => 'Receiver', 'type' => 'text'],
                    ['key' => 'receiver_phone', 'label' => 'Phone', 'type' => 'text'],
                    ['key' => 'route', 'label' => 'Route', 'type' => 'text'],
                    ['key' => 'status', 'label' => 'Status', 'type' => 'status'],
                    ['key' => 'arrived_at', 'label' => 'Arrived', 'type' => 'datetime'],
                    ['key' => 'days', 'label' => 'Days waiting', 'type' => 'int'],
                    ['key' => 'bucket', 'label' => 'Group', 'type' => 'text'],
                ],
                'rows' => $rows,
                'totals' => null,
            ]],
            'notes' => ['A snapshot of cargo waiting right now; date filters do not apply.'],
        ];
    }

    // ── Helpers ──────────────────────────────────────────────

    /**
     * Consignments with the common filters applied. $dateColumn is what the date range filters on.
     */
    private function consignments(array $f, string $dateColumn = 'c.created_at'): Builder
    {
        return DB::table('consignments as c')
            ->leftJoin('transport_services as ts', 'ts.id', '=', 'c.transport_service_id')
            ->whereNull('c.deleted_at')
            ->when($f['date_from'] ?? null, fn ($q, $d) => $q->whereDate($dateColumn, '>=', $d))
            ->when($f['date_to'] ?? null, fn ($q, $d) => $q->whereDate($dateColumn, '<=', $d))
            ->when($f['route_id'] ?? null, fn ($q, $id) => $q->where('c.route_id', $id))
            ->when($f['status'] ?? null, fn ($q, $s) => $q->where('c.status', $s))
            ->when($f['payment_status'] ?? null, fn ($q, $s) => $q->where('c.payment_status', $s))
            ->when($f['payment_method'] ?? null, fn ($q, $m) => $q->where('c.payment_method', $m))
            ->when($f['booked_by'] ?? null, fn ($q, $u) => $q->where('c.booked_by_user_id', $u));
    }

    private function sum(array $rows, array $keys): array
    {
        return collect($keys)->mapWithKeys(fn ($k) => [$k => array_sum(array_map('floatval', array_column($rows, $k)))])->all();
    }

    /**
     * Rows flattened for Excel: column label => plain value.
     */
    /**
     * Value charge per consignment (sum of its items), to join onto consignment queries.
     */
    private function itemValueCharges(): Builder
    {
        return DB::table('cargo_items')
            ->whereNull('deleted_at')
            ->groupBy('consignment_id')
            ->selectRaw('consignment_id, SUM(value_charge) AS value_charge');
    }

    public function exportRows(array $section): array
    {
        return array_map(function ($row) use ($section) {
            $out = [];
            foreach ($section['columns'] as $col) {
                $v = $row[$col['key']] ?? null;
                $out[$col['label']] = match ($col['type']) {
                    'int' => (int) $v,
                    'num', 'money', 'kg' => $v === null ? null : round((float) $v, 2),
                    'pct' => $v === null ? null : (float) $v,
                    'status', 'payment', 'attempt' => $v ? Str::headline($v) : null,
                    'datetime' => $v ? Carbon::parse($v)->format('Y-m-d H:i') : null,
                    default => $v,
                };
            }

            return $out;
        }, $section['rows']);
    }
}
