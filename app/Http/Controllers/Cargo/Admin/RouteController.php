<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Station;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
    use SubmitsDualControl;

    public function index(Request $request)
    {
        $query = Route::with(['origin', 'destination'])->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $routes = $query->paginate(10)->appends($request->query());
        return view('admin.cargo.routes.index', compact('routes'));
    }

    public function create()
    {
        $stations = Station::where('status', 'active')->orderBy('name')->get();
        return view('admin.cargo.routes.create', compact('stations'));
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'origin_station_id' => 'required|exists:stations,id',
            'destination_station_id' => 'required|exists:stations,id|different:origin_station_id',
            'distance_nm' => 'nullable|numeric|min:0.1',
        ]);

        $origin = Station::findOrFail($validated['origin_station_id']);
        $destination = Station::findOrFail($validated['destination_station_id']);

        return $this->submitChange($request, 'route_create', 'route', 0, ['after' => $this->routeAttributes($validated, $origin, $destination)],
            'Route created.', route('admin.cargo.routes.index'));
    }

    public function show(string $id)
    {
        $route = Route::findOrFail($id);
        $route->load('agents');
        $allAgents = \App\Models\Agent::where('is_active', true)->orderBy('agency_name')->get();
        $priceCard = app(\App\Services\Cargo\CargoFareService::class)->routeRateCard($route);
        return view('admin.cargo.routes.show', compact('route', 'allAgents', 'priceCard'));
    }

    public function edit(string $id)
    {
        $route = Route::findOrFail($id);
        $stations = Station::where('status', 'active')->orderBy('name')->get();
        return view('admin.cargo.routes.edit', compact('route', 'stations'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'origin_station_id' => 'required|exists:stations,id',
            'destination_station_id' => 'required|exists:stations,id|different:origin_station_id',
            'distance_nm' => 'nullable|numeric|min:0.1',
        ]);

        $origin = Station::findOrFail($validated['origin_station_id']);
        $destination = Station::findOrFail($validated['destination_station_id']);

        $payloadBefore = $route->toArray();
        $payloadAfter = array_merge($payloadBefore, $this->routeAttributes($validated, $origin, $destination));

        return $this->submitChange($request, 'route_update', 'route', $route->id,
            ['before' => $payloadBefore, 'after' => $payloadAfter], 'Route updated.', route('admin.cargo.routes.index'));
    }

    public function destroy(string $id)
    {
        //
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $route = Route::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $payloadBefore = $route->toArray();
        $payloadAfter = array_merge($route->toArray(), ['status' => $validated['status']]);

        return $this->submitChange($request, 'route_status_update', 'route', $route->id,
            ['before' => $payloadBefore, 'after' => $payloadAfter], $validated['status'] === 'active' ? 'Route activated.' : 'Route suspended.');
    }

    /**
     * The form's station fields as the route's columns (the route stores them as origin/destination port ids).
     */
    private function routeAttributes(array $validated, Station $origin, Station $destination): array
    {
        return [
            'origin_port_id' => $origin->id,
            'destination_port_id' => $destination->id,
            'distance_nm' => $validated['distance_nm'] ?? null,
            'name' => $origin->name . ' - ' . $destination->name,
            'code' => $origin->code . '-' . $destination->code,
        ];
    }

    public function updateAgents(Request $request, DualControlService $dcService, string $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'agents'            => 'nullable|array',
            'agents.*.selected' => 'nullable|boolean',
            'agents.*.status'   => 'nullable|in:active,suspended',
        ]);

        $syncData = [];
        if (!empty($validated['agents'])) {
            foreach ($validated['agents'] as $agentId => $data) {
                if (!empty($data['selected'])) {
                    $syncData[$agentId] = ['status' => $data['status'] ?? 'active'];
                }
            }
        }

        return $this->submitChange($request, 'route_agents_update', 'route', $route->id, [
            'before' => ['agents' => $route->agents->mapWithKeys(fn ($a) => [$a->id => ['status' => $a->pivot->status]])->toArray()],
            'after' => ['agents' => $syncData],
        ], 'Assigned agents updated.');
    }
}
