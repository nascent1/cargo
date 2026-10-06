<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JourneyController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        $journeys = TransportService::with(['route', 'transportUnit', 'schedule.trip'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get();

        return view('admin.cargo.journeys.index', compact('journeys', 'date'));
    }

    public function availabilityIndex(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        
        $journeys = TransportService::with(['route', 'transportUnit', 'schedule.trip'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get();

        return view('admin.cargo.journeys.availability-index', compact('journeys', 'date'));
    }

    public function updateStatus(Request $request, TransportService $journey)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);

        $journey->update(['status' => $validated['status']]);

        return back()->with('success', 'Journey status updated successfully.');
    }

    public function updateWeather(Request $request, TransportService $journey)
    {
        $validated = $request->validate([
            'weather_notes' => 'nullable|string|max:1000',
        ]);

        $journey->update(['weather_notes' => $validated['weather_notes']]);

        return back()->with('success', 'Weather notes updated successfully.');
    }

    public function manageAvailability(TransportService $journey)
    {
        $journey->load(['transportUnit.cargoCapacities', 'route.origin', 'route.destination']);
        
        $agents = \App\Models\Agent::where('is_active', true)->orderBy('agency_name')->get();
        
        // Fetch or create seat inventories for this journey
        $inventories = [];
        foreach ($journey->transportUnit->cargoCapacities as $ferryClass) {
            $inventory = \App\Models\CargoInventory::firstOrCreate(
                [
                    'journey_id' => $journey->id,
                    'travel_class_id' => $ferryClass->id
                ],
                [
                    'total_seats' => $ferryClass->capacity,
                    'sold_seats' => 0,
                    'held_seats' => 0,
                    'blocked_seats' => 0
                ]
            );
            
            // Load allocations
            $inventory->load('allocations.agent');
            
            $inventories[] = [
                'class' => $ferryClass,
                'inventory' => $inventory,
                'available' => max(0, $inventory->total_seats - $inventory->sold_seats - $inventory->held_seats - $inventory->blocked_seats)
            ];
        }

        return view('admin.cargo.journeys.availability', compact('journey', 'inventories', 'agents'));
    }

    public function updateAvailability(Request $request, TransportService $journey, \App\Services\DualControlService $dcService)
    {
        $validated = $request->validate([
            'inventories' => 'required|array',
            'inventories.*.id' => 'required|exists:cargo_inventories,id',
            'inventories.*.total_seats' => 'required|integer|min:0',
            'inventories.*.allocations' => 'nullable|array',
            'inventories.*.allocations.*.agent_id' => 'nullable|exists:agents,id',
            'inventories.*.allocations.*.allocated_seats' => 'nullable|integer|min:0',
        ]);

        if ($dcService->requiresDualControl('capacity_update')) {
            $dcService->submit('capacity_update', 'cargo_inventories', $journey->id, ['after' => $validated['inventories']]);
            return redirect()->route('admin.cargo.journeys.index')->with('success', 'Capacity update submitted for dual control approval.');
        } else {
            foreach ($validated['inventories'] as $invData) {
                $inventory = \App\Models\CargoInventory::findOrFail($invData['id']);
                $inventory->update([
                    'total_seats' => $invData['total_seats']
                ]);
                
                if (isset($invData['allocations'])) {
                    // Sync allocations
                    $inventory->allocations()->delete();
                    foreach ($invData['allocations'] as $allocation) {
                        if (!empty($allocation['agent_id']) && $allocation['allocated_seats'] > 0) {
                            $inventory->allocations()->create([
                                'agent_id' => $allocation['agent_id'],
                                'allocated_seats' => $allocation['allocated_seats']
                            ]);
                        }
                    }
                }
            }
            return redirect()->route('admin.cargo.journeys.index')->with('success', 'Journey capacity updated successfully.');
        }
    }
}
