<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\TransportService;

class CargoManifestService
{
    /** Bookings that physically travel (paid; not cancelled) */
    public const MANIFEST_STATUSES = ['booked', 'loaded', 'in_transit', 'delivered', 'unclaimed', 'collected'];

    /**
     * Generate a manifest summary for a specific transport service.
     * Totals come from the items themselves. The original keys are kept for the scanner API.
     */
    public function generateManifest($identifier): array
    {
        $service = TransportService::with([
            'transportUnit',
            'route.origin',
            'route.destination',
            'cargoInventory',
            'consignments' => fn ($q) => $q->whereIn('status', self::MANIFEST_STATUSES)->orderBy('consignment_number'),
            'consignments.items.cargoType',
        ])->where(function ($q) use ($identifier) {
            $q->where('service_number', (string) $identifier);
            if (ctype_digit((string) $identifier)) {
                $q->orWhere('id', (int) $identifier);
            }
        })->firstOrFail();

        $summary = [
            'service_id' => $service->id,
            'service_number' => $service->service_number,
            'unit_name' => $service->transportUnit->name ?? 'Unknown',
            'unit_registration' => $service->transportUnit->registration_number ?? null,
            'route' => ($service->route->origin->name ?? '') . ' to ' . ($service->route->destination->name ?? ''),
            'route_name' => $service->route->name ?? null,
            'departure' => $service->departure_date . ' ' . $service->departure_time,
            'departure_date' => $service->departure_date,
            'departure_time' => $service->departure_time ? substr($service->departure_time, 0, 5) : null,
            'service_status' => $service->status,
            'total_consignments' => 0,
            'total_items' => 0,
            'total_pieces' => 0,
            'total_weight_kg' => 0,
            'total_volume_cbm' => 0,
            'total_lane_meters' => 0,
            'hazmat_count' => 0,
            'fragile_count' => 0,
            'livestock_count' => 0,
            'vehicle_count' => 0,
            'items_by_type' => [],
            'by_type' => [],
            'capacity' => null,
            // Booked on this departure but not paid — they must not be loaded
            'awaiting_payment' => Consignment::where('transport_service_id', $service->id)->where('status', 'pending_payment')->count(),
            // Port operations: loading is open until the vessel leaves
            'can_load' => !in_array($service->status, CargoOperationsService::CLOSED_JOURNEY, true) && (bool) auth()->user()?->hasPermission('cargo-load'),
            'consignments' => [],
            'generated_at' => now()->format('d M Y H:i'),
            'generated_by' => auth()->user()?->full_name,
        ];

        if ($inv = $service->cargoInventory) {
            $summary['capacity'] = [
                'weight_kg' => (float) $inv->total_weight_capacity_kg,
                'volume_cbm' => (float) $inv->total_volume_capacity_cbm,
                'lane_meters' => (float) $inv->total_lane_meters,
            ];
        }

        foreach ($service->consignments as $consignment) {
            $summary['total_consignments']++;
            if ($consignment->has_hazmat) $summary['hazmat_count']++;
            if ($consignment->has_livestock) $summary['livestock_count']++;

            $cData = [
                'id' => $consignment->id,
                'consignment_number' => $consignment->consignment_number,
                'sender' => $consignment->sender_name,
                'sender_phone' => $consignment->sender_phone,
                'receiver' => $consignment->receiver_name,
                'receiver_phone' => $consignment->receiver_phone,
                'status' => $consignment->status,
                'left_behind' => $consignment->isLeftBehind(),
                'special_instructions' => $consignment->special_instructions,
                'has_hazmat' => (bool) $consignment->has_hazmat,
                'weight_kg' => 0,
                'items' => [],
            ];

            foreach ($consignment->items as $item) {
                if ($item->status === 'cancelled') {
                    continue;
                }
                $qty = max(1, (int) $item->quantity);
                $weight = (float) $item->weight_kg;
                $volume = (float) $item->volume_cbm;
                $typeName = $item->cargoType->name ?? 'Unknown';
                $isHazmat = (bool) ($item->cargoType->requires_hazmat_declaration ?? false);

                $summary['total_items']++;
                $summary['total_pieces'] += $qty;
                $summary['total_weight_kg'] += $weight;
                $summary['total_volume_cbm'] += $volume;
                $summary['total_lane_meters'] += (float) $item->lane_meters_consumed;
                if ($item->is_fragile) $summary['fragile_count']++;
                if ($item->vehicle_reg_number || ($item->cargoType->measurement_unit ?? null) === 'lane_meter') $summary['vehicle_count'] += $qty;
                $cData['weight_kg'] += $weight;

                $summary['items_by_type'][$typeName] = ($summary['items_by_type'][$typeName] ?? 0) + 1;
                $summary['by_type'][$typeName] ??= ['type' => $typeName, 'lines' => 0, 'pieces' => 0, 'weight_kg' => 0, 'volume_cbm' => 0];
                $summary['by_type'][$typeName]['lines']++;
                $summary['by_type'][$typeName]['pieces'] += $qty;
                $summary['by_type'][$typeName]['weight_kg'] += $weight;
                $summary['by_type'][$typeName]['volume_cbm'] += $volume;

                $cData['items'][] = [
                    // The item's label (QR code token) — what loaders scan
                    'barcode' => $item->qr_code_token,
                    'item_number' => $item->item_number,
                    'type' => $typeName,
                    'description' => $item->description,
                    'quantity' => $qty,
                    'weight' => $weight,
                    'volume_cbm' => $volume,
                    'vehicle_reg_number' => $item->vehicle_reg_number,
                    'driver_name' => $item->driver_name,
                    'status' => $item->status,
                    'is_fragile' => (bool) $item->is_fragile,
                    'is_hazmat' => $isHazmat,
                ];
            }

            $summary['consignments'][] = $cData;
        }

        $summary['total_weight_kg'] = round($summary['total_weight_kg'], 2);
        $summary['total_volume_cbm'] = round($summary['total_volume_cbm'], 3);
        $summary['total_lane_meters'] = round($summary['total_lane_meters'], 2);
        $summary['by_type'] = array_values($summary['by_type']);

        return $summary;
    }
}
