<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\TransportUnit;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use SubmitsDualControl;

    public function index()
    {
        $schedules = Schedule::with(['trip.route', 'transportUnit'])->latest()->paginate(10);
        return view('admin.cargo.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $trips = Trip::with('route')->where('status', 'active')->get();
        $units = TransportUnit::where('status', 'active')->get();
        return view('admin.cargo.schedules.create', compact('trips', 'units'));
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'transport_unit_id' => 'required|exists:transport_units,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        // A recurring vessel schedule (departures are generated from it) — the same record the vessel profile's
        // Schedules tab creates. It used to be submitted as a departure (cargo_schedule_create), which could never apply.
        return $this->submitChange($request, 'schedule_create', 'schedule', 0, ['after' => [
            'trip_id' => (int) $validated['trip_id'],
            'ferry_id' => (int) $validated['transport_unit_id'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'] ?? null,
            'status' => 'active',
        ]], 'Schedule added.', route('admin.cargo.schedules.index'));
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        //
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(string $id)
    {
        //
    }
}
