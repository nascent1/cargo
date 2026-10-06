<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportUnit;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class TransportUnitController extends Controller
{
    public function index(Request $request)
    {
        $query = TransportUnit::with('cargoCapacities')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('registration_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $units = $query->paginate(10)->appends($request->query());
        return view('admin.cargo.transport-units.index', compact('units'));
    }

    public function create()
    {
        return view('admin.cargo.transport-units.create');
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'registration_number' => 'required|string|max:50|unique:transport_units,registration_number',
            'type' => 'required|string|max:50',
            'transport_type' => 'required|in:ferry,bus,truck',
            'has_cargo_deck' => 'boolean',
            'status' => 'required|in:active,inactive,maintenance',
            'capacity_lane_meters' => 'nullable|numeric|min:0',
            'capacity_weight_kg' => 'nullable|numeric|min:0',
            'capacity_volume_cbm' => 'nullable|numeric|min:0',
        ]);
        
        $validated['has_cargo_deck'] = $request->has('has_cargo_deck');

        $dcService->submit('cargo_transport_unit_create', 'cargo_transport_unit', 0, ['after' => $validated]);

        return redirect()->route('admin.cargo.transport-units.index')->with('success', 'Transport Unit creation submitted successfully.');
    }

    public function show(string $id)
    {
        $unit = TransportUnit::with('cargoCapacities')->findOrFail($id);
        return view('admin.cargo.transport-units.show', compact('unit'));
    }

    public function edit(string $id)
    {
        $unit = TransportUnit::with('cargoCapacities')->findOrFail($id);
        return view('admin.cargo.transport-units.edit', compact('unit'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $unit = TransportUnit::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'registration_number' => 'required|string|max:50|unique:transport_units,registration_number,' . $unit->id,
            'type' => 'required|string|max:50',
            'transport_type' => 'required|in:ferry,bus,truck',
            'has_cargo_deck' => 'boolean',
            'status' => 'required|in:active,inactive,maintenance',
            'capacity_lane_meters' => 'nullable|numeric|min:0',
            'capacity_weight_kg' => 'nullable|numeric|min:0',
            'capacity_volume_cbm' => 'nullable|numeric|min:0',
        ]);
        
        $validated['has_cargo_deck'] = $request->has('has_cargo_deck');

        $payloadBefore = $unit->toArray();
        if ($unit->has_cargo_deck) {
            $cap = $unit->cargoCapacities()->first();
            $payloadBefore['capacity_lane_meters'] = $cap->total_lane_meters ?? 0;
            $payloadBefore['capacity_weight_kg'] = $cap->total_weight_capacity_kg ?? 0;
            $payloadBefore['capacity_volume_cbm'] = $cap->total_volume_capacity_cbm ?? 0;
        }

        $payloadAfter = array_merge($unit->toArray(), $validated);

        $dcService->submit('cargo_transport_unit_update', 'cargo_transport_unit', $unit->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->route('admin.cargo.transport-units.index')->with('success', 'Transport Unit update submitted successfully.');
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $unit = TransportUnit::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,maintenance',
        ]);

        $payloadBefore = $unit->toArray();
        $payloadAfter = array_merge($unit->toArray(), ['status' => $validated['status']]);

        $dcService->submit('cargo_transport_unit_status_update', 'cargo_transport_unit', $unit->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->back()->with('success', "Transport Unit status update submitted successfully.");
    }

    public function storeSchedule(Request $request, DualControlService $dcService, string $id)
    {
        $unit = TransportUnit::findOrFail($id);

        $validated = $request->validate([
            'trip_id' => 'required|exists:trips,id',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:active,inactive',
            'days' => 'required|array',
            'days.*' => 'in:1,2,3,4,5,6,7'
        ]);

        $payload = [
            'trip_id' => $validated['trip_id'],
            'ferry_id' => $unit->id,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => $validated['status'],
            'frequency_pattern' => json_encode($validated['days'])
        ];

        $dcService->submit('cargo_schedule_create', 'cargo_schedule', 0, ['after' => $payload]);

        return redirect()->back()->with('success', 'Schedule creation submitted for approval successfully.');
    }

    public function destroySchedule(Request $request, DualControlService $dcService, string $id, string $scheduleId)
    {
        $unit = TransportUnit::findOrFail($id);
        $schedule = \App\Models\Schedule::where('ferry_id', $unit->id)->findOrFail($scheduleId);

        // Standard pattern for deletion via dual control
        $payloadBefore = $schedule->toArray();
        $payloadAfter = array_merge($schedule->toArray(), ['status' => 'inactive']); // Or handle actual deletion if Maker Checker supports it

        $dcService->submit('cargo_schedule_update', 'cargo_schedule', $schedule->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->back()->with('success', 'Schedule deactivation submitted for approval successfully.');
    }
}
