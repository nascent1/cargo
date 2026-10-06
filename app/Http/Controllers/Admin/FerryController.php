<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ferry;
use App\Services\DualControlService;
use App\Services\SeatLayoutGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FerryController extends Controller
{
    public function index(Request $request)
    {
        $query = Ferry::with('classes')->latest();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%")
                  ->orWhere('port_of_registry', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $ferries = $query->paginate(10)->appends($request->query());
        return view('admin.ferries.index', compact('ferries'));
    }

    public function create()
    {
        return view('admin.ferries.create');
    }

    public function show(Ferry $ferry)
    {
        $ferry->load('classes');
        return view('admin.ferries.show', compact('ferry'));
    }

    public function edit(Ferry $ferry)
    {
        return view('admin.ferries.edit', compact('ferry'));
    }

    public function update(Request $request, DualControlService $dcService, SeatLayoutGeneratorService $layoutService, Ferry $ferry)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'nullable|string|max:50',
            'port_of_registry' => 'nullable|string|max:100',
            'capacity' => 'required|integer|min:1',
            'has_layout' => 'boolean',
            'layout_image' => 'nullable|image|max:2048',
        ]);

        $payloadAfter = array_merge($ferry->toArray(), $validated);

        if ($request->has('has_layout')) {
            $payloadAfter['has_layout'] = true;
        } else {
            $payloadAfter['has_layout'] = false;
        }

        // Remove the uploaded file from the generic payload merge
        unset($payloadAfter['layout_image']);

        if ($request->hasFile('layout_image')) {
            $path = $request->file('layout_image')->store('seat_layouts', 'public');
            $payloadAfter['layout_image_path'] = $path;
            
            // Mark layout as processing. Job will run after Dual Control approval.
            $payloadAfter['seat_layout_json'] = 'processing';
        }

        $payloadBefore = $ferry->toArray();

        $dcService->submit('ferry_update', 'ferry', $ferry->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        return redirect()->route('admin.ferries.index')->with('success', 'Ferry update submitted successfully.');
    }

    public function updateStatus(Request $request, DualControlService $dcService, string $id)
    {
        $ferry = Ferry::findOrFail($id);
        
        $validated = $request->validate([
            'status' => 'required|in:active,inactive',
        ]);

        $payloadBefore = $ferry->toArray();
        $payloadAfter = array_merge($ferry->toArray(), ['status' => $validated['status']]);

        $dcService->submit('ferry_status_update', 'ferry', $ferry->id, [
            'before' => $payloadBefore,
            'after' => $payloadAfter,
        ]);

        $action = $validated['status'] === 'active' ? 'activation' : 'suspension';
        return redirect()->back()->with('success', "Ferry {$action} submitted successfully.");
    }

    public function updateLayout(Request $request, Ferry $ferry)
    {
        // Directly update for this utility, or could go through DualControl
        $validated = $request->validate([
            'seat_layout_json' => 'required|array',
        ]);

        $layoutData = $validated['seat_layout_json'];
        
        // Compute capacities from layout — supports both v1 (flat) and v2 (decks array)
        $classCounts = [];
        $roomCounts  = [];
        $totalFerryCapacity = 0;

        // Collect all seats regardless of version
        $allSeats = [];
        if (!empty($layoutData['decks']) && is_array($layoutData['decks'])) {
            // v2: iterate each deck
            foreach ($layoutData['decks'] as $deck) {
                if (!empty($deck['seats']) && is_array($deck['seats'])) {
                    foreach ($deck['seats'] as $seat) {
                        $allSeats[] = $seat;
                    }
                }
            }
        } elseif (!empty($layoutData['seats']) && is_array($layoutData['seats'])) {
            // v1: flat seats array
            $allSeats = $layoutData['seats'];
        }

        foreach ($allSeats as $seat) {
            if (!empty($seat['class_id'])) {
                $cId = $seat['class_id'];
                $classCounts[$cId] = ($classCounts[$cId] ?? 0) + 1;
                $totalFerryCapacity++;
                
                if (!empty($seat['room_id'])) {
                    $rId = $seat['room_id'];
                    $roomCounts[$rId] = ($roomCounts[$rId] ?? 0) + 1;
                }
            }
        }
        
        // Sync capacities to database models
        foreach ($classCounts as $classId => $count) {
            \App\Models\FerryClass::where('id', $classId)->update(['no_seats' => $count]);
        }
        foreach ($roomCounts as $roomId => $count) {
            \App\Models\FerryClassRoom::where('id', $roomId)->update(['capacity' => $count]);
        }

        $ferry->update([
            'seat_layout_json' => $layoutData,
            'has_layout' => true,
            'capacity' => $totalFerryCapacity > 0 ? $totalFerryCapacity : $ferry->capacity,
        ]);

        return response()->json(['success' => true, 'message' => 'Layout saved successfully. Capacities synced.']);
    }

    public function editor(Ferry $ferry)
    {
        $ferry->load('classes');
        return view('admin.ferries.editor', compact('ferry'));
    }

    public function getClasses(Ferry $ferry)
    {
        return response()->json($ferry->classes()->with('rooms')->get()->sortBy('name')->values()->all());
    }

    public function generateAiLayout(Request $request, Ferry $ferry, SeatLayoutGeneratorService $layoutService)
    {
        $validated = $request->validate([
            'zones' => 'required|array',
            'deckIndex' => 'nullable|integer',
        ]);

        $imagePath = null;

        if ($request->has('deckIndex') && isset($ferry->seat_layout_json['decks'][$request->deckIndex])) {
            $deck = $ferry->seat_layout_json['decks'][$request->deckIndex];
            if (!empty($deck['backgroundImage'])) {
                // Convert public URL to local storage path
                $pattern = '/\/storage\/(.+)$/';
                if (preg_match($pattern, $deck['backgroundImage'], $matches)) {
                    $imagePath = storage_path('app/public/' . $matches[1]);
                }
            }
        } elseif ($ferry->layout_image_path) {
            $imagePath = storage_path('app/public/' . $ferry->layout_image_path);
        }

        if (!$imagePath || !file_exists($imagePath)) {
            return response()->json(['error' => 'No layout image found for this ferry/deck'], 400);
        }

        try {
            $classes = $ferry->classes->toArray();
            
            $jsonLayout = $layoutService->analyzeImageWithZones($imagePath, $classes, $validated['zones']);
            
            return response()->json($jsonLayout);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateCertificate(Request $request, Ferry $ferry)
    {
        $validated = $request->validate([
            'certificate_number' => 'required|string|max:255',
            'issued_by' => 'nullable|string|max:255',
            'issued_date' => 'required|date',
            'expiry_date' => 'required|date|after_or_equal:issued_date',
            'max_crew' => 'required|integer|min:1',
            'max_passengers' => 'nullable|integer|min:1',
            'notes' => 'nullable|string|max:1000',
        ]);

        $cert = $ferry->certificates()->latest('issued_date')->first();

        if ($cert) {
            $cert->update($validated);
        } else {
            $ferry->certificates()->create(array_merge($validated, [
                'certificate_type' => 'Safe Manning',
                'status' => 'active'
            ]));
        }

        return back()->with('success', 'Safe Manning Certificate updated successfully.');
    }
}
