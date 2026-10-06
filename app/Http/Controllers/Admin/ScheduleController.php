<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\Ferry;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    public function index()
    {
        $schedules = Schedule::with(['trip.route', 'ferry'])->latest()->paginate(10);
        return view('admin.schedules.index', compact('schedules'));
    }

    public function create()
    {
        $trips = Trip::with('route')->where('status', 'active')->get();
        $ferries = Ferry::where('status', 'active')->get();
        return view('admin.schedules.create', compact('trips', 'ferries'));
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'ferry_id' => 'required|exists:ferries,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $dcService->submit('schedule_create', 'schedule', 0, ['after' => $validated]);

        return redirect()->route('admin.schedules.index')->with('success', 'Schedule creation submitted successfully.');
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
