<?php

namespace App\Http\Controllers\Cargo;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consignment;

class TrackingController extends Controller
{
    /**
     * Public page view for tracking.
     */
    public function view(Request $request, $consignmentNumber = null)
    {
        return view('cargo.tracking.index', compact('consignmentNumber'));
    }

    /**
     * API for public tracking.
     */
    public function track(Request $request, $consignmentNumber)
    {
        $consignment = Consignment::with([
            'items' => function($q) {
                $q->select('id', 'consignment_id', 'cargo_type_id', 'item_number', 'status', 'barcode', 'description');
            },
            'items.cargoType' => function($q) {
                $q->select('id', 'name', 'icon');
            },
            'trackingEvents' => function($q) {
                $q->whereNull('cargo_item_id')->orderBy('created_at', 'desc'); // Consignment level events
            },
            'route.origin',
            'route.destination'
        ])
        ->select('id', 'consignment_number', 'status', 'route_id', 'sender_name', 'receiver_name', 'estimated_arrival_at', 'created_at')
        ->where('consignment_number', $consignmentNumber)
        ->first();

        if (!$consignment) {
            return response()->json(['error' => 'Consignment not found'], 404);
        }

        // Mask PII for public view
        $consignment->sender_name = $this->maskName($consignment->sender_name);
        $consignment->receiver_name = $this->maskName($consignment->receiver_name);

        return response()->json($consignment);
    }

    private function maskName(string $name): string
    {
        $parts = explode(' ', trim($name));
        $masked = [];
        foreach ($parts as $index => $part) {
            if ($index === 0) { // Keep first name
                $masked[] = $part;
            } else {
                // Mask surname to first letter only
                $masked[] = substr($part, 0, 1) . '***';
            }
        }
        return implode(' ', $masked);
    }
}
