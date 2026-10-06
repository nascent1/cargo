<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Voyage;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VoyageController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        $voyages = Voyage::with(['route', 'ferry', 'schedule.trip'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get();

        return view('admin.voyages.index', compact('voyages', 'date'));
    }

    public function availabilityIndex(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        
        $voyages = Voyage::with(['route', 'ferry', 'schedule.trip'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get();

        return view('admin.voyages.availability-index', compact('voyages', 'date'));
    }

    public function updateStatus(Request $request, Voyage $voyage)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,boarding,departed,arrived,cancelled',
        ]);

        $voyage->update(['status' => $validated['status']]);

        return back()->with('success', 'Voyage status updated successfully.');
    }

    public function updateWeather(Request $request, Voyage $voyage)
    {
        $validated = $request->validate([
            'weather_notes' => 'nullable|string|max:1000',
        ]);

        $voyage->update(['weather_notes' => $validated['weather_notes']]);

        return back()->with('success', 'Weather notes updated successfully.');
    }

    public function manageAvailability(Voyage $voyage)
    {
        $voyage->load(['ferry.classes', 'route.origin', 'route.destination']);
        
        $agents = \App\Models\Agent::where('is_active', true)->orderBy('agency_name')->get();
        
        // Fetch or create seat inventories for this voyage
        $inventories = [];
        foreach ($voyage->ferry->classes as $ferryClass) {
            $inventory = \App\Models\SeatInventory::firstOrCreate(
                [
                    'voyage_id' => $voyage->id,
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

        return view('admin.voyages.availability', compact('voyage', 'inventories', 'agents'));
    }

    public function updateAvailability(Request $request, Voyage $voyage, \App\Services\DualControlService $dcService)
    {
        $validated = $request->validate([
            'inventories' => 'required|array',
            'inventories.*.id' => 'required|exists:seat_inventories,id',
            'inventories.*.total_seats' => 'required|integer|min:0',
            'inventories.*.allocations' => 'nullable|array',
            'inventories.*.allocations.*.agent_id' => 'nullable|exists:agents,id',
            'inventories.*.allocations.*.allocated_seats' => 'nullable|integer|min:0',
        ]);

        if ($dcService->requiresDualControl('capacity_update')) {
            $dcService->submit('capacity_update', 'seat_inventories', $voyage->id, ['after' => $validated['inventories']]);
            return redirect()->route('admin.voyages.index')->with('success', 'Capacity update submitted for dual control approval.');
        } else {
            foreach ($validated['inventories'] as $invData) {
                $inventory = \App\Models\SeatInventory::findOrFail($invData['id']);
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
                                'allocated_seats' => $allocation['allocated_seats'],
                                'can_view_increased' => $allocation['can_view_increased'] ?? true,
                                'max_sell_count' => !empty($allocation['max_sell_count']) ? $allocation['max_sell_count'] : null,
                            ]);
                        }
                    }
                }
            }
            return redirect()->route('admin.voyages.index')->with('success', 'Voyage capacity updated successfully.');
        }
    }
}
