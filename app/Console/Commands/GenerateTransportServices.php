<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Models\TransportService;
use App\Models\TransportUnit;
use App\Models\CargoInventory;
use Carbon\Carbon;

class GenerateTransportServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cargo:generate-services {--days=7 : Number of days ahead to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily transport service (journey) records based on active schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Generating transport services (journeys) for the next {$days} days...");

        // Note: The schedule uses ferry_id for the database column but relates to TransportUnit
        $schedules = Schedule::with('trip')->where('status', 'active')->get();
        $generatedCount = 0;

        foreach ($schedules as $schedule) {
            $startDate = now();
            
            // Respect schedule's valid start/end dates
            if ($schedule->start_date && $startDate->lt(Carbon::parse($schedule->start_date))) {
                $startDate = Carbon::parse($schedule->start_date);
            }

            for ($i = 0; $i < $days; $i++) {
                $targetDate = $startDate->copy()->addDays($i);
                
                if ($schedule->end_date && $targetDate->gt(Carbon::parse($schedule->end_date))) {
                    continue; // Past schedule end date
                }

                $shouldGenerate = true;
                if ($schedule->frequency_pattern) {
                    $allowedDays = array_map('trim', explode(',', $schedule->frequency_pattern));
                    if (!empty($allowedDays) && !in_array($targetDate->format('l'), $allowedDays)) {
                        $shouldGenerate = false;
                    }
                }

                if (!$shouldGenerate) {
                    continue;
                }

                $exists = TransportService::where('schedule_id', $schedule->id)
                    ->whereDate('departure_date', $targetDate->format('Y-m-d'))
                    ->exists();

                if (!$exists) {
                    $service = TransportService::create([
                        'schedule_id' => $schedule->id,
                        'route_id' => $schedule->trip->route_id,
                        'transport_unit_id' => $schedule->ferry_id,
                        'departure_date' => $targetDate->format('Y-m-d'),
                        'departure_time' => $schedule->trip->departure_time,
                        'status' => 'scheduled',
                    ]);

                    // Initialize Cargo Inventory if unit has cargo deck
                    $unit = TransportUnit::find($schedule->ferry_id);
                    if ($unit && $unit->has_cargo_deck) {
                        $capacity = collect($unit->cargoCapacities)->first();
                        CargoInventory::create([
                            'transport_service_id' => $service->id,
                            'transport_unit_id' => $service->transport_unit_id,
                            'total_lane_meters' => $capacity ? $capacity->total_lane_meters : 0,
                            'total_weight_capacity_kg' => $capacity ? $capacity->total_weight_capacity_kg : 0,
                            'total_volume_capacity_cbm' => $capacity ? $capacity->total_volume_capacity_cbm : 0,
                        ]);
                    }

                    $generatedCount++;
                }
            }
        }

        $this->info("Successfully generated {$generatedCount} new transport services.");
    }
}
