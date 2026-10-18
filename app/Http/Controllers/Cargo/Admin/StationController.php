<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class StationController extends Controller
{
    use SubmitsDualControl;

    public function index(Request $request)
    {
        $query = Station::latest();

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

        $stations = $query->paginate(10)->appends($request->query());
        return view('admin.cargo.stations.index', compact('stations'));
    }

    public function create()
    {
        return view('admin.cargo.stations.create');
    }

    public function store(Request $request, DualControlService $dcService)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:stations,code',
            'city' => 'nullable|string|max:100',
        ]);

        return $this->submitChange($request, 'station_create', 'station', 0, ['after' => $validated],
            'Station created.', route('admin.cargo.stations.index'));
    }

    public function show(string $id)
    {
        //
    }

    public function edit(string $id)
    {
        $station = Station::findOrFail($id);
        return view('admin.cargo.stations.edit', compact('station'));
    }

    public function update(Request $request, DualControlService $dcService, string $id)
    {
        $station = Station::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:10|unique:stations,code,' . $station->id,
            'city' => 'nullable|string|max:100',
        ]);

        $payloadBefore = $station->toArray();
        $payloadAfter = array_merge($station->toArray(), $validated);

        return $this->submitChange($request, 'station_update', 'station', $station->id,
            ['before' => $payloadBefore, 'after' => $payloadAfter], 'Station updated.', route('admin.cargo.stations.index'));
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $station = Station::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $payloadBefore = $station->toArray();
        $payloadAfter = array_merge($station->toArray(), ['status' => $validated['status']]);

        return $this->submitChange($request, 'station_status_update', 'station', $station->id,
            ['before' => $payloadBefore, 'after' => $payloadAfter], $validated['status'] === 'active' ? 'Station activated.' : 'Station suspended.');
    }
}
