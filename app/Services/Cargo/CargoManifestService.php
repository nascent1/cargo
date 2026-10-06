<?php

namespace App\Services\Cargo;

use App\Models\TransportService;

class CargoManifestService
{
    /**
     * Generate a manifest summary for a specific transport service.
     */
    public function generateManifest($identifier): array
    {
        $service = TransportService::with([
            'transportUnit',
            'route.origin',
            'route.destination',
            'consignments' => function($q) {
                $q->whereIn('status', ['booked', 'loaded', 'in_transit', 'delivered', 'collected']);
            },
            'consignments.items.cargoType'
        ])->where(function($q) use ($identifier) {
            $q->where('id', $identifier)->orWhere('service_number', $identifier);
        })->firstOrFail();

        $summary = [
            'service_id' => $service->id,
            'unit_name' => $service->transportUnit->name ?? 'Unknown',
            'route' => ($service->route->origin->name ?? '') . ' to ' . ($service->route->destination->name ?? ''),
            'departure' => $service->departure_date . ' ' . $service->departure_time,
            'total_consignments' => 0,
            'total_items' => 0,
            'total_weight_kg' => 0,
            'total_volume_cbm' => 0,
            'hazmat_count' => 0,
            'livestock_count' => 0,
            'items_by_type' => [],
            'consignments' => []
        ];

        foreach ($service->consignments as $consignment) {
            $summary['total_consignments']++;
            $summary['total_weight_kg'] += $consignment->total_weight_kg;
            $summary['total_volume_cbm'] += $consignment->total_volume_cbm;
            
            if ($consignment->has_hazmat) $summary['hazmat_count']++;
            if ($consignment->has_livestock) $summary['livestock_count']++;

            $cData = [
                'consignment_number' => $consignment->consignment_number,
                'sender' => $consignment->sender_name,
                'receiver' => $consignment->receiver_name,
                'status' => $consignment->status,
                'items' => []
            ];

            foreach ($consignment->items as $item) {
                $summary['total_items']++;
                
                $typeName = $item->cargoType->name ?? 'Unknown';
                if (!isset($summary['items_by_type'][$typeName])) {
                    $summary['items_by_type'][$typeName] = 0;
                }
                $summary['items_by_type'][$typeName]++;

                $cData['items'][] = [
                    'barcode' => $item->barcode,
                    'type' => $typeName,
                    'weight' => $item->weight_kg,
                    'status' => $item->status,
                    'is_fragile' => $item->is_fragile
                ];
            }

            $summary['consignments'][] = $cData;
        }

        return $summary;
    }
}
