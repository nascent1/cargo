<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Route;
use App\Models\Port;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class RouteController extends Controller
{
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
        return view('admin.routes.index', compact('routes'));
    }

    public function create()
    {
        $ports = Port::where('status', 'active')->orderBy('name')->get();
        return view('admin.routes.create', compact('ports'));
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'origin_port_id' => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            'distance_nm' => 'nullable|numeric|min:0.1',
        ]);

        $origin = Port::findOrFail($validated['origin_port_id']);
        $destination = Port::findOrFail($validated['destination_port_id']);

        $validated['name'] = $origin->name . ' - ' . $destination->name;
        $validated['code'] = $origin->code . '-' . $destination->code;

        $dcService->submit('route_create', 'route', 0, ['after' => $validated]);

        return redirect()->route('admin.routes.index')->with('success', 'Route creation submitted successfully.');
    }

    public function show(string $id)
    {
        $route = Route::findOrFail($id);
        $route->load('agents');
        $allAgents = \App\Models\Agent::where('is_active', true)->orderBy('agency_name')->get();
        return view('admin.routes.show', compact('route', 'allAgents'));
    }

    public function edit(string $id)
    {
        $route = Route::findOrFail($id);
        $ports = Port::where('status', 'active')->orderBy('name')->get();
        return view('admin.routes.edit', compact('route', 'ports'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $route = Route::findOrFail($id);

        $validated = $request->validate([
            'origin_port_id' => 'required|exists:ports,id',
            'destination_port_id' => 'required|exists:ports,id|different:origin_port_id',
            'distance_nm' => 'nullable|numeric|min:0.1',
        ]);

        $origin = Port::findOrFail($validated['origin_port_id']);
        $destination = Port::findOrFail($validated['destination_port_id']);

        $validated['name'] = $origin->name . ' - ' . $destination->name;
        $validated['code'] = $origin->code . '-' . $destination->code;

        $payloadBefore = $route->toArray();
        $payloadAfter = array_merge($route->toArray(), $validated);

        $dcService->submit('route_update', 'route', $route->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->route('admin.routes.index')->with('success', 'Route update submitted successfully.');
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

        $dcService->submit('route_status_update', 'route', $route->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        $action = $validated['status'] === 'active' ? 'activation' : 'suspension';
        return redirect()->back()->with('success', "Route {$action} submitted successfully.");
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

        if ($dcService->requiresDualControl('route_agents_update')) {
            $dcReq = $dcService->submit('route_agents_update', 'route', $route->id, [
                'before' => ['agents' => $route->agents->mapWithKeys(fn($a) => [$a->id => ['status' => $a->pivot->status]])->toArray()],
                'after'  => ['agents' => $syncData],
            ]);
            return redirect()->back()->with('success', "Assigned agents update requires Maker-Checker approval (Request #{$dcReq->id} submitted).");
        }

        $route->agents()->sync($syncData);

        return redirect()->back()->with('success', 'Assigned agents updated successfully.');
    }
}
