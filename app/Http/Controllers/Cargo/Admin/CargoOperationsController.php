<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Models\TransportService;
use App\Services\Cargo\CargoHandoverService;
use App\Services\Cargo\CargoOperationsService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Port operations in the admin portal: loading (booking page and Loading Manifest), hand-over with
 * a confirmed mobile, and supervisor status corrections. Departing and arriving are on Journeys.
 */
class CargoOperationsController extends Controller
{
    public function __construct(private CargoOperationsService $operations, private CargoHandoverService $handover)
    {
    }

    public function load(Request $request, Consignment $consignment): RedirectResponse
    {
        try {
            $this->operations->load($consignment, $request->user()->id, 'booking page');
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$consignment->consignment_number} marked loaded.");
    }

    /**
     * Loading Manifest: tick bookings and "Mark loaded", or scan / type a booking number or item label.
     */
    public function loadFromManifest(Request $request, TransportService $transportService): JsonResponse
    {
        $data = $request->validate([
            'ids' => 'array|max:500',
            'ids.*' => 'integer',
            'code' => 'nullable|string|max:60',
        ]);

        if (filled($data['code'] ?? null)) {
            $consignment = $this->operations->findOnDeparture($transportService, $data['code']);
            if (!$consignment) {
                return response()->json(['message' => "“{$data['code']}” is not on this departure."], 422);
            }
            $consignments = collect([$consignment]);
        } else {
            $consignments = Consignment::where('transport_service_id', $transportService->id)->whereIn('id', $data['ids'] ?? [])->get();
        }

        $result = $this->operations->loadMany($transportService, $consignments, $request->user()->id, filled($data['code'] ?? null) ? 'scanned' : 'manifest');
        $loaded = count($result['loaded']);

        return response()->json($result + [
            'message' => $loaded ? "{$loaded} booking(s) marked loaded." : 'Nothing was loaded.',
        ], $loaded || !$result['skipped'] ? 200 : 422);
    }

    /** Supervisor fix with a reason (cargo-status-correct). */
    public function correct(Request $request, Consignment $consignment): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(CargoOperationsService::CORRECTABLE)],
            'reason' => 'required|string|min:5|max:500',
        ]);

        try {
            $this->operations->correct($consignment, $data['status'], $data['reason'], $request->user(), $request->ip());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Status corrected to ' . str_replace('_', ' ', $data['status']) . '.');
    }

    public function handoverCode(Request $request, Consignment $consignment): JsonResponse
    {
        $data = $request->validate(['phone' => 'required|string|max:20']);

        try {
            return response()->json($this->handover->sendCode($consignment, $data['phone']));
        } catch (DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function handover(Request $request, Consignment $consignment): RedirectResponse
    {
        $data = $request->validate(self::handoverRules());

        try {
            $result = $this->handover->handOver($consignment, $data, $request->user(), $request->ip());
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', "{$consignment->consignment_number} handed over to {$consignment->fresh()->collected_by_name}.")
            ->with('handover_warnings', $result['warnings']);
    }

    public static function handoverRules(): array
    {
        return [
            'collector_name' => 'required|string|max:150',
            'id_type' => ['required', Rule::in(array_keys(CargoHandoverService::ID_TYPES))],
            'id_number' => 'required|string|max:60',
            'phone' => 'required|string|max:20',
            'code' => 'nullable|string|max:10',
            'unconfirmed_reason' => 'nullable|string|max:255',
        ];
    }
}
