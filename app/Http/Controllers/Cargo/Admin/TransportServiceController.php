<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportService;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Services\DualControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\Schedule;

class TransportServiceController extends Controller
{
    public function index(Request $request)
    {
        $query = TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'cargoInventory'])->latest();

        if ($request->filled('date')) {
            $query->where('departure_date', $request->date);
        }
        
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $services = $query->paginate(15)->appends($request->query());
        $routes = Route::where('status', 'active')->orderBy('name')->get();
        return view('admin.cargo.journeys.index', compact('services', 'routes'));
    }

    public function create()
    {
        $routes = Route::where('status', 'active')->orderBy('name')->get();
        $units = TransportUnit::where('status', 'active')->orderBy('name')->get();
        return view('admin.cargo.journeys.create', compact('routes', 'units'));
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'transport_unit_id' => 'required|exists:transport_units,id',
            'schedule_id' => 'nullable|exists:schedules,id',
            'departure_date' => 'required|date',
            'departure_time' => 'required',
            'transport_type' => 'required|in:ferry,bus,truck',
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);

        $validated['service_number'] = 'SVC-' . strtoupper(Str::random(8));

        $dcService->submit('cargo_schedule_create', 'cargo_schedule', 0, ['after' => $validated]);

        return redirect()->route('admin.cargo.journeys.index')->with('success', 'Journey creation submitted successfully.');
    }

    public function show(string $id)
    {
        $service = TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'cargoInventory'])->findOrFail($id);
        return view('admin.cargo.journeys.show', compact('service'));
    }

    public function edit(string $id)
    {
        $service = TransportService::findOrFail($id);
        $routes = Route::where('status', 'active')->orderBy('name')->get();
        $units = TransportUnit::where('status', 'active')->orderBy('name')->get();
        return view('admin.cargo.journeys.edit', compact('service', 'routes', 'units'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $service = TransportService::findOrFail($id);

        $validated = $request->validate([
            'route_id' => 'required|exists:routes,id',
            'transport_unit_id' => 'required|exists:transport_units,id',
            'departure_date' => 'required|date',
            'departure_time' => 'required',
            'transport_type' => 'required|in:ferry,bus,truck',
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);

        $payloadBefore = $service->toArray();
        $payloadAfter = array_merge($service->toArray(), $validated);

        $dcService->submit('cargo_schedule_update', 'cargo_schedule', $service->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->route('admin.cargo.journeys.index')->with('success', 'Journey update submitted successfully.');
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $service = TransportService::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);

        $payloadBefore = $service->toArray();
        $payloadAfter = array_merge($service->toArray(), ['status' => $validated['status']]);

        $dcService->submit('cargo_schedule_status_update', 'cargo_schedule', $service->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->back()->with('success', "Journey status update submitted successfully.");
    }
}
