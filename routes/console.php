<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\CheckExpiringCertifications;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── UCLP Cargo Scheduled Jobs ───────────────────────────
Schedule::command('cargo:clear-locks')->everyMinute()->name('cargo-clear-locks')->withoutOverlapping();
Schedule::command('cargo:payments-sweep')->everyMinute()->name('cargo-payments-sweep')->withoutOverlapping();
Schedule::command('cargo:generate-services', ['--days' => 30])->dailyAt('00:05')->name('generate-services')->withoutOverlapping();
Schedule::command('voyage:generate', ['--days' => 30])->dailyAt('00:05')->name('generate-voyages')->withoutOverlapping();
Schedule::command('cargo:auto-transit')->everyFifteenMinutes()->name('cargo-auto-transit')->withoutOverlapping();
Schedule::command('cargo:auto-arrived')->hourly()->name('cargo-auto-arrived')->withoutOverlapping();
Schedule::command('cargo:mark-unclaimed')->dailyAt('02:00')->name('cargo-mark-unclaimed')->withoutOverlapping();
Schedule::command('reports:snapshot')->dailyAt('01:00');
Schedule::command('financials:reconcile')->dailyAt('02:30')->name('cargo-financial-reconciliation')->withoutOverlapping();

// Dual control: apply scheduled changes when their start time comes, expire unapproved requests
Schedule::command('dual-control:process')->everyMinute()->name('dual-control-process')->withoutOverlapping();

// Close of business: SMS summary once the day's last cargo departure has left (+ buffer)
Schedule::command('notifications:close-business-day')->everyFiveMinutes()->name('close-business-day')->withoutOverlapping();
