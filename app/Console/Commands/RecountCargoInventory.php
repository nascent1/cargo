<?php

namespace App\Console\Commands;

use App\Models\CargoInventory;
use App\Models\CargoLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rebuilds cargo capacity figures from the bookings themselves.
 *
 * 1. Items: lane meters used = cargo type lane meters × quantity, where it was never recorded.
 * 2. Consignments: total weight / volume / pieces = sum of their items, where they disagree.
 * 3. Departures: used = sum over live (not cancelled) bookings; held = sum over unexpired holds.
 *    Expired holds are removed.
 *
 * Dry run unless --apply is given.
 */
class RecountCargoInventory extends Command
{
    protected $signature = 'cargo:recount-inventory {--apply : Write the changes (default is a dry run)}';
    protected $description = 'Recompute consignment totals and departure capacity (used/held) from bookings';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? 'Applying changes.' : 'Dry run — nothing will be written. Re-run with --apply to save.');

        DB::beginTransaction();

        try {
            $items = $this->backfillItemLaneMeters();
            $consignments = $this->backfillConsignmentTotals();
            $expired = CargoLock::where('expires_at', '<', now())->count();
            CargoLock::where('expires_at', '<', now())->delete();
            $departures = $this->recountDepartures();

            $this->newLine();
            $this->line("Items given lane meters: {$items}");
            $this->line("Consignments with corrected totals: {$consignments}");
            $this->line("Expired holds removed: {$expired}");
            $this->line('Departures with corrected capacity: ' . count($departures));

            if ($departures) {
                $this->table(
                    ['Departure', 'Date', 'Field', 'Before', 'After'],
                    collect($departures)->flatMap(fn ($d) => collect($d['changes'])->map(fn ($c, $field) => [
                        $d['service'], $d['date'], $field, $c[0], $c[1],
                    ])->values())->all()
                );
            }

            $apply ? DB::commit() : DB::rollBack();
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function backfillItemLaneMeters(): int
    {
        return DB::update(<<<'SQL'
            UPDATE cargo_items ci
               SET lane_meters_consumed = ct.lane_meters * GREATEST(ci.quantity, 1)
              FROM cargo_types ct
             WHERE ct.id = ci.cargo_type_id
               AND ct.lane_meters > 0
               AND COALESCE(ci.lane_meters_consumed, 0) = 0
        SQL);
    }

    private function backfillConsignmentTotals(): int
    {
        return DB::update(<<<'SQL'
            UPDATE consignments c
               SET total_weight_kg = t.weight, total_volume_cbm = t.volume, total_items = t.pieces
              FROM (SELECT consignment_id,
                           COALESCE(SUM(weight_kg), 0) AS weight,
                           COALESCE(SUM(volume_cbm), 0) AS volume,
                           COALESCE(SUM(GREATEST(quantity, 1)), 0) AS pieces
                      FROM cargo_items GROUP BY consignment_id) t
             WHERE t.consignment_id = c.id
               AND (c.total_weight_kg <> t.weight OR c.total_volume_cbm <> t.volume OR c.total_items <> t.pieces)
        SQL);
    }

    private function recountDepartures(): array
    {
        $used = DB::table('consignments as c')
            ->where('c.status', '!=', 'cancelled')
            ->whereNull('c.deleted_at')
            ->groupBy('c.transport_service_id')
            ->selectRaw('c.transport_service_id, SUM(c.total_weight_kg) AS weight, SUM(c.total_volume_cbm) AS volume')
            ->get()->keyBy('transport_service_id');

        $lanes = DB::table('cargo_items as i')
            ->join('consignments as c', 'c.id', '=', 'i.consignment_id')
            ->where('c.status', '!=', 'cancelled')
            ->whereNull('c.deleted_at')
            ->groupBy('c.transport_service_id')
            ->selectRaw('c.transport_service_id, SUM(COALESCE(i.lane_meters_consumed, 0)) AS lanes')
            ->pluck('lanes', 'transport_service_id');

        $held = DB::table('cargo_locks')
            ->where('expires_at', '>=', now())
            ->groupBy('transport_service_id')
            ->selectRaw('transport_service_id, SUM(weight_kg) AS weight, SUM(volume_cbm) AS volume, SUM(lane_meters) AS lanes')
            ->get()->keyBy('transport_service_id');

        $changed = [];
        CargoInventory::with('transportService')->orderBy('transport_service_id')->lockForUpdate()->get()
            ->each(function (CargoInventory $inv) use ($used, $lanes, $held, &$changed) {
                $sid = $inv->transport_service_id;
                $target = [
                    'used_weight_kg' => round((float) ($used[$sid]->weight ?? 0), 2),
                    'used_volume_cbm' => round((float) ($used[$sid]->volume ?? 0), 2),
                    'used_lane_meters' => round((float) ($lanes[$sid] ?? 0), 2),
                    'held_weight_kg' => round((float) ($held[$sid]->weight ?? 0), 2),
                    'held_volume_cbm' => round((float) ($held[$sid]->volume ?? 0), 2),
                    'held_lane_meters' => round((float) ($held[$sid]->lanes ?? 0), 2),
                ];

                $changes = [];
                foreach ($target as $field => $value) {
                    if (abs((float) $inv->{$field} - $value) > 0.004) {
                        $changes[$field] = [(float) $inv->{$field}, $value];
                    }
                }

                if ($changes) {
                    $inv->forceFill($target)->save();
                    $changed[] = [
                        'service' => $inv->transportService?->service_number ?? "#{$sid}",
                        'date' => $inv->transportService?->departure_date,
                        'changes' => $changes,
                    ];
                }
            });

        return $changed;
    }
}
