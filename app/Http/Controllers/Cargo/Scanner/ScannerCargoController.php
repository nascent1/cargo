<?php

namespace App\Http\Controllers\Cargo\Scanner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Consignment;
use App\Models\CargoItem;
use App\Services\Cargo\CargoTrackingService;
use App\Services\Cargo\CargoManifestService;

class ScannerCargoController extends Controller
{
    protected $trackingSvc;
    protected $manifestSvc;

    public function __construct(CargoTrackingService $trackingSvc, CargoManifestService $manifestSvc)
    {
        $this->trackingSvc = $trackingSvc;
        $this->manifestSvc = $manifestSvc;
    }

    /**
     * Scan a barcode (could be consignment or item level).
     */
    public function scan(Request $request)
    {
        $request->validate(['barcode' => 'required|string']);
        $barcode = $request->barcode;

        // Try item first (qr_code_token is the physical barcode)
        $item = CargoItem::with('consignment')->where('qr_code_token', $barcode)->first();
        
        if ($item) {
            return response()->json([
                'type' => 'item',
                'data' => $item
            ]);
        }

        // Try consignment
        $consignment = Consignment::with('items')->where('consignment_number', $barcode)->first();
        
        if ($consignment) {
            return response()->json([
                'type' => 'consignment',
                'data' => $consignment
            ]);
        }

        return response()->json(['error' => 'Barcode not found in system'], 404);
    }

    /**
     * Scan actions. Loading follows the port rules (paid, vessel not yet left; scanning every item
     * loads the booking); "offloaded" marks an item off the vessel at the destination. In transit
     * and arrived come from the journey; collection needs the hand-over with a confirmed mobile.
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|in:item,consignment',
            'id' => 'required|integer',
            'status' => ['required', Rule::in(['accepted', 'loaded', 'in_transit', 'offloaded', 'delivered', 'collected', 'returned'])],
            'location' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $status = $request->status;
        $permission = ['loaded' => 'cargo-load', 'offloaded' => 'cargo-offload'][$status] ?? null;
        if (!$permission) {
            return response()->json(['error' => match ($status) {
                'in_transit', 'delivered' => 'In transit and arrived are set when the journey departs or arrives (Operations › Journeys).',
                'collected' => 'Collection needs the hand-over with the collector\'s ID and a confirmed mobile.',
                default => 'This scan action is not used.',
            }], 422);
        }
        if (!$request->user()->hasPermission($permission)) {
            return response()->json(['error' => 'You do not have permission for this scan action.'], 403);
        }

        $operations = app(\App\Services\Cargo\CargoOperationsService::class);
        $userId = $request->user()->id;

        try {
            if ($request->type === 'consignment') {
                $consignment = Consignment::findOrFail($request->id);
                if ($status === 'offloaded') {
                    return response()->json(['error' => 'Scan the items to mark them offloaded.'], 422);
                }
                $operations->load($consignment, $userId, 'scanned');

                return response()->json(['message' => "{$consignment->consignment_number} marked loaded."]);
            }

            $item = CargoItem::with('consignment')->findOrFail($request->id);
            if ($status === 'loaded') {
                $bookingLoaded = $operations->loadItem($item, $userId);

                return response()->json(['message' => $bookingLoaded
                    ? "Item loaded — all items of {$item->consignment->consignment_number} are on board."
                    : 'Item loaded.', 'booking_loaded' => $bookingLoaded]);
            }

            if (!in_array($item->consignment?->status, ['in_transit', 'delivered', 'unclaimed'], true)) {
                return response()->json(['error' => 'Only cargo that has travelled can be offloaded.'], 422);
            }
            $this->trackingSvc->logItemEvent($item, 'offloaded', $request->description ?? 'Item offloaded', $userId, $request->location);

            return response()->json(['message' => 'Item offloaded.']);
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function getManifest(Request $request, $transportServiceId)
    {
        try {
            $manifest = $this->manifestSvc->generateManifest($transportServiceId);
            return response()->json($manifest);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
