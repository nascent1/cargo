<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Port;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class PortController extends Controller
{
    public function index(Request $request)
    {
        $query = Port::latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('city', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $ports = $query->paginate(10)->appends($request->query());
        return view('admin.ports.index', compact('ports'));
    }

    public function create()
    {
        return view('admin.ports.create');
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:ports,code',
            'city' => 'nullable|string|max:100',
        ]);

        $dcService->submit('port_create', 'port', 0, ['after' => $validated]);

        return redirect()->route('admin.ports.index')->with('success', 'Port creation submitted successfully.');
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $port = Port::findOrFail($id);
        return view('admin.ports.edit', compact('port'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $port = Port::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:ports,code,' . $port->id,
            'city' => 'nullable|string|max:100',
        ]);

        $payloadBefore = $port->toArray();
        $payloadAfter = array_merge($port->toArray(), $validated);

        $dcService->submit('port_update', 'port', $port->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->route('admin.ports.index')->with('success', 'Port update submitted successfully.');
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $port = Port::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $payloadBefore = $port->toArray();
        $payloadAfter = array_merge($port->toArray(), ['status' => $validated['status']]);

        $dcService->submit('port_status_update', 'port', $port->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        $action = $validated['status'] === 'active' ? 'activation' : 'suspension';
        return redirect()->back()->with('success', "Port {$action} submitted successfully.");
    }
}
