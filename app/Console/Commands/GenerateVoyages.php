<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateVoyages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'voyage:generate {--days=7 : Number of days ahead to generate}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate daily voyage records based on active schedules';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $this->info("Generating voyages for the next {$days} days...");

        $schedules = \App\Models\Schedule::with('trip')->where('status', 'active')->get();
        $generatedCount = 0;

        foreach ($schedules as $schedule) {
            $startDate = now();
            
            // Respect schedule's valid start/end dates
            if ($schedule->start_date && $startDate->lt(\Carbon\Carbon::parse($schedule->start_date))) {
                $startDate = \Carbon\Carbon::parse($schedule->start_date);
            }

            for ($i = 0; $i < $days; $i++) {
                $targetDate = $startDate->copy()->addDays($i);
                
                if ($schedule->end_date && $targetDate->gt(\Carbon\Carbon::parse($schedule->end_date))) {
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

                $exists = \App\Models\Voyage::where('schedule_id', $schedule->id)
                    ->whereDate('departure_date', $targetDate->format('Y-m-d'))
                    ->exists();

                if (!$exists) {
                    \App\Models\Voyage::create([
                        'schedule_id' => $schedule->id,
                        'route_id' => $schedule->trip->route_id,
                        'ferry_id' => $schedule->ferry_id,
                        'departure_date' => $targetDate->format('Y-m-d'),
                        'departure_time' => $schedule->trip->departure_time,
                        'status' => 'scheduled',
                    ]);
                    $generatedCount++;
                }
            }
        }

        $this->info("Successfully generated {$generatedCount} new voyages.");
    }
}
