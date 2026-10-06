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
     * Update status (e.g. Load, Offload, Deliver)
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'type' => 'required|in:item,consignment',
            'id' => 'required|integer',
            'status' => ['required', Rule::in(['accepted', 'loaded', 'in_transit', 'offloaded', 'delivered', 'collected', 'returned'])],
            'location' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        try {
            $userId = auth()->id();
            
            if ($request->type === 'item') {
                $item = CargoItem::findOrFail($request->id);
                if ($item->consignment?->status === 'pending_payment') {
                    return response()->json(['error' => 'Payment pending — this cargo cannot be processed until the booking is paid.'], 422);
                }
                $this->trackingSvc->logItemEvent($item, $request->status, $request->description ?? "Status updated to {$request->status}", $userId, $request->location);
            } else {
                $consignment = Consignment::findOrFail($request->id);
                if ($consignment->status === 'pending_payment') {
                    return response()->json(['error' => 'Payment pending — this cargo cannot be processed until the booking is paid.'], 422);
                }
                $this->trackingSvc->logEvent($consignment, $request->status, $request->description ?? "Status updated to {$request->status}", $userId, $request->location);
            }

            return response()->json(['message' => 'Status updated successfully']);
        } catch (\Exception $e) {
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
