<?php

namespace Tests\Feature;

use App\Models\DualControlConfig;
use App\Models\DualControlRequest;
use App\Models\Station;
use App\Models\User;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\DualControlRegistry;
use App\Services\DualControlService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\Finder\Finder;
use Tests\TestCase;

/**
 * The dual-control registry covers every action the code submits, and scheduled changes
 * are applied at their time or fail safely.
 *
 * Runs against the configured DB inside a transaction (DatabaseTransactions) — never refreshes it.
 */
class DualControlRegistryTest extends TestCase
{
    use DatabaseTransactions;

    public function test_every_operation_submitted_in_code_is_registered_with_a_working_handler(): void
    {
        $used = [];
        foreach ((new Finder())->files()->in(app_path())->name('*.php') as $file) {
            // $dualControl->submit('op', …), submitChange($request, 'op', …), submitDualControl('op', …) — not a controller's own $this->submit()
            preg_match_all("/(?:(?<!\\\$this)->submit|submitChange\\(\\\$request,|submitDualControl)\\(?\\s*'([a-z0-9_]+)'/", $file->getContents(), $m);
            array_push($used, ...$m[1]);
        }
        $used = array_unique($used);
        $this->assertNotEmpty($used);

        foreach ($used as $op) {
            $this->assertTrue(DualControlRegistry::has($op), "Operation '{$op}' is submitted but not in config/dual_control.php");
        }
        foreach (DualControlRegistry::all() as $key => $op) {
            if ($op['handler']) {
                $this->assertTrue(is_subclass_of($op['handler'], DualControlHandler::class), "{$key}: handler {$op['handler']} must implement DualControlHandler");
            }
            $this->assertContains($op['timing'], ['immediate', 'scheduled', 'dated'], "{$key}: unknown timing");
        }
    }

    public function test_sync_registers_every_operation_and_keeps_existing_toggles(): void
    {
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'station_update')->update(['requires_dual_control' => false]);

        DualControlConfig::syncFromRegistry();

        $this->assertFalse((bool) DualControlConfig::where('operation_type', 'station_update')->value('requires_dual_control'));
        $this->assertSame(count(DualControlRegistry::all()), DualControlConfig::whereIn('operation_type', array_keys(DualControlRegistry::all()))->count());
    }

    public function test_a_scheduled_change_is_applied_at_its_time_or_fails_safely_when_the_record_changed(): void
    {
        $maker = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first() ?? $this->markTestSkipped('Needs an admin user.');
        $station = Station::first() ?? $this->markTestSkipped('Needs a station.');
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'station_update')->update(['requires_dual_control' => false]);
        Auth::setUser($maker);
        $dc = app(DualControlService::class);

        // Applied when its time comes
        $when = now()->addDay();
        $req = $dc->submit('station_update', 'station', $station->id, ['before' => $station->toArray(), 'after' => array_merge($station->toArray(), ['city' => 'Scheduled City'])], ['commence_at' => $when]);
        $this->assertSame('scheduled', $req->status);
        $this->assertNotSame('Scheduled City', $station->fresh()->city);

        Carbon::setTestNow($when->copy()->addMinute());
        $this->artisan('dual-control:process')->assertSuccessful();
        Carbon::setTestNow();
        $this->assertSame('Scheduled City', $station->fresh()->city);
        $this->assertSame('approved', $req->fresh()->status);

        // Someone edits the same field before a scheduled change runs: it fails, nothing is overwritten
        $station->refresh();
        $req = $dc->submit('station_update', 'station', $station->id, ['before' => $station->toArray(), 'after' => array_merge($station->toArray(), ['city' => 'Overwrite'])], ['commence_at' => now()->addDay()]);
        $station->update(['city' => 'Edited meanwhile']);

        Carbon::setTestNow(now()->addDays(2));
        $this->artisan('dual-control:process')->assertSuccessful();
        Carbon::setTestNow();
        $this->assertSame('failed', $req->fresh()->status);
        $this->assertSame('Edited meanwhile', $station->fresh()->city);
    }

    public function test_requests_with_a_start_stay_open_until_that_start_and_then_expire(): void
    {
        $maker = User::whereHas('roles', fn ($q) => $q->where('slug', 'admin'))->first() ?? $this->markTestSkipped('Needs an admin user.');
        $station = Station::first() ?? $this->markTestSkipped('Needs a station.');
        DualControlConfig::syncFromRegistry();
        DualControlConfig::where('operation_type', 'station_status_update')->update(['requires_dual_control' => true]);
        Auth::setUser($maker);

        $req = app(DualControlService::class)->submit('station_status_update', 'station', $station->id,
            ['before' => $station->toArray(), 'after' => array_merge($station->toArray(), ['status' => 'inactive'])], ['commence_at' => now()->addDays(10)]);

        Carbon::setTestNow(now()->addDays(5)); // well past the old 48h limit
        app(DualControlService::class)->processDue();
        $this->assertSame('pending', $req->fresh()->status);

        Carbon::setTestNow(now()->addDays(8)); // past start + 48h
        app(DualControlService::class)->processDue();
        Carbon::setTestNow();
        $this->assertSame('expired', $req->fresh()->status);
    }
}
