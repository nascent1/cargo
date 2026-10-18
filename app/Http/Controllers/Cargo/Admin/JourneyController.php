<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\CargoInventory;
use App\Models\TransportService;
use App\Services\Cargo\CargoInventoryService;
use App\Services\Cargo\CargoOperationsService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Carbon\Carbon;

class JourneyController extends Controller
{
    use SubmitsDualControl;

    /** Capacity dimensions the booking engine checks (CargoInventoryService::available) */
    public const DIMENSIONS = [
        'lane_meters' => ['label' => 'Vehicle deck (lane metres)', 'unit' => 'm', 'total' => 'total_lane_meters', 'used' => 'used_lane_meters', 'held' => 'held_lane_meters', 'standard' => 'total_lane_meters'],
        'weight_kg' => ['label' => 'Weight', 'unit' => 'kg', 'total' => 'total_weight_capacity_kg', 'used' => 'used_weight_kg', 'held' => 'held_weight_kg', 'standard' => 'total_weight_capacity_kg'],
        'volume_cbm' => ['label' => 'Volume', 'unit' => 'cbm', 'total' => 'total_volume_capacity_cbm', 'used' => 'used_volume_cbm', 'held' => 'held_volume_cbm', 'standard' => 'total_volume_capacity_cbm'],
    ];

    public function index(Request $request)
    {
        $date = $request->input('date', Carbon::today()->format('Y-m-d'));
        
        $journeys = TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'schedule.trip'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get();
        // Cargo on each departure, for the Depart / Arrive dialogs
        $operations = app(CargoOperationsService::class);
        $cargo = $journeys->mapWithKeys(fn (TransportService $j) => [$j->id => $operations->departurePreview($j)]);

        return view('admin.cargo.journeys.index', compact('journeys', 'date', 'cargo'));
    }

    /**
     * Scheduled / boarding / cancelled — through dual control (cargo_journey_status_update).
     * Departed and arrived have their own actions because they move the cargo on board.
     */
    public function updateStatus(Request $request, TransportService $journey)
    {
        $validated = $request->validate([
            'status' => 'required|in:scheduled,boarding,cancelled',
        ]);
        if (in_array($journey->status, ['departed', 'arrived'], true)) {
            return back()->with('error', 'This journey has already ' . $journey->status . '.');
        }

        return $this->submitChange($request, 'cargo_journey_status_update', 'transport_service', $journey->id, [
            'before' => ['status' => $journey->status],
            'after' => ['status' => $validated['status']],
        ], 'Journey status updated.');
    }

    /**
     * The vessel left: loaded cargo goes in transit, paid bookings not loaded are left behind
     * (CargoJourneyStatusHandler, once the change is applied).
     */
    public function depart(Request $request, TransportService $journey)
    {
        if (!in_array($journey->status, ['scheduled', 'boarding'], true)) {
            return back()->with('error', "This journey can't depart: it is {$journey->status}.");
        }
        $preview = app(CargoOperationsService::class)->departurePreview($journey);

        return $this->submitChange($request, 'cargo_journey_status_update', 'transport_service', $journey->id, [
            'before' => ['status' => $journey->status],
            'after' => ['status' => 'departed', 'loaded' => $preview['loaded'], 'left_behind' => $preview['not_loaded']],
        ], "Departed: {$preview['loaded']} booking(s) in transit" . ($preview['not_loaded'] ? ", {$preview['not_loaded']} left behind." : '.'));
    }

    /** The vessel arrived: cargo in transit is ready for collection. */
    public function arrive(Request $request, TransportService $journey)
    {
        if ($journey->status !== 'departed') {
            return back()->with('error', 'Only a departed journey can arrive.');
        }
        $preview = app(CargoOperationsService::class)->departurePreview($journey);

        return $this->submitChange($request, 'cargo_journey_status_update', 'transport_service', $journey->id, [
            'before' => ['status' => $journey->status],
            'after' => ['status' => 'arrived', 'arriving' => $preview['in_transit']],
        ], "Arrived: {$preview['in_transit']} booking(s) ready for collection.");
    }

    // Weather notes are information only (not under dual control)
    public function updateWeather(Request $request, TransportService $journey)
    {
        $validated = $request->validate([
            'weather_notes' => 'nullable|string|max:1000',
        ]);

        $journey->update(['weather_notes' => $validated['weather_notes']]);

        return back()->with('success', 'Weather notes updated successfully.');
    }

    /**
     * Departures of a day with their cargo space, linking to the capacity screen.
     */
    public function availabilityIndex(Request $request, CargoInventoryService $inventorySvc)
    {
        $date = $request->input('date', now()->toDateString());

        $journeys = TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'cargoInventory'])
            ->whereDate('departure_date', $date)
            ->orderBy('departure_time')
            ->get()
            ->each(fn (TransportService $s) => $s->setAttribute('space', $inventorySvc->spaceSummary($s->cargoInventory)[1]));

        return view('admin.cargo.journeys.availability-index', compact('journeys', 'date'));
    }

    /**
     * One departure's cargo capacity: total, booked, held and free per dimension.
     * Without an inventory row yet, the vessel's standard capacity is offered.
     */
    public function manageAvailability(TransportService $journey)
    {
        $journey->load(['transportUnit.cargoCapacities', 'route.origin', 'route.destination', 'cargoInventory']);
        $inventory = $journey->cargoInventory;
        $standard = $journey->transportUnit?->cargoCapacities->first();

        $dimensions = collect(self::DIMENSIONS)->map(fn ($d, $key) => array_merge($d, [
            'key' => $key,
            'total' => (float) ($inventory?->{$d['total']} ?? $standard?->{$d['standard']} ?? 0),
            'used' => (float) ($inventory?->{$d['used']} ?? 0),
            'held' => (float) ($inventory?->{$d['held']} ?? 0),
            'standard' => (float) ($standard?->{$d['standard']} ?? 0),
        ]))->values();

        return view('admin.cargo.journeys.availability', compact('journey', 'inventory', 'dimensions'));
    }

    /**
     * Change a departure's capacity through dual control (cargo_capacity_update). A total can't go
     * below what is already booked or held; that is checked again when the change is applied.
     */
    public function updateAvailability(Request $request, TransportService $journey)
    {
        $rules = collect(self::DIMENSIONS)->mapWithKeys(fn ($d, $key) => [$key => 'required|numeric|min:0|max:1000000'])->all();
        $validated = $request->validate($rules + ['reason' => 'nullable|string|max:255']);

        $inventory = $journey->cargoInventory;
        $errors = [];
        foreach (self::DIMENSIONS as $key => $d) {
            $taken = (float) ($inventory?->{$d['used']} ?? 0) + (float) ($inventory?->{$d['held']} ?? 0);
            if ((float) $validated[$key] < $taken) {
                $errors[$key] = "{$d['label']} can't be less than the {$taken} {$d['unit']} already booked or held.";
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $totals = fn (?CargoInventory $inv) => collect(self::DIMENSIONS)->mapWithKeys(fn ($d) => [$d['total'] => (float) ($inv?->{$d['total']} ?? 0)])->all();
        $after = collect(self::DIMENSIONS)->mapWithKeys(fn ($d, $key) => [$d['total'] => (float) $validated[$key]])->all();

        return $this->submitChange($request, 'cargo_capacity_update', 'cargo_inventory', $inventory?->id ?? 0, [
            'before' => $inventory ? $totals($inventory) : null,
            'after' => $after + ['transport_service_id' => $journey->id, 'reason' => $validated['reason'] ?? null],
        ], 'Departure capacity updated.', route('admin.cargo.journeys.availability', $journey), [
            'subject_key' => "transport_service:{$journey->id}:capacity",
        ]);
    }
}
