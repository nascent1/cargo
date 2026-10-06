<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\CargoItem;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\DB;

class CargoTrackingService
{
    /**
     * Log an event for the entire consignment.
     */
    public function logEvent(Consignment $consignment, string $status, string $description, ?int $userId = null, ?string $location = null, ?array $metadata = null)
    {
        DB::transaction(function () use ($consignment, $status, $description, $userId, $location, $metadata) {
            
            // Create event for consignment
            TrackingEvent::create([
                'consignment_id' => $consignment->id,
                'event_type' => 'state_change',
                'to_status' => $status,
                'notes' => $description,
                'location_name' => $location,
                'performed_by_id' => $userId,
                'metadata' => $metadata,
                'created_at' => now(),
            ]);

            // Create event for all items associated (unless item level tracking is overriding)
            foreach ($consignment->items as $item) {
                TrackingEvent::create([
                    'consignment_id' => $consignment->id,
                    'cargo_item_id' => $item->id,
                    'event_type' => 'state_change',
                    'to_status' => $status,
                    'notes' => $description,
                    'location_name' => $location,
                    'performed_by_id' => $userId,
                    'created_at' => now(),
                ]);

                // Update item status
                $item->status = $status;
                $item->save();
            }

            // Update main consignment status
            $consignment->status = $status;
            
            if ($status === 'collected') {
                $consignment->collected_at = now();
            } elseif ($status === 'delivered') { // Arrived at destination port
                $consignment->delivered_at = now();
            }

            $consignment->save();
        });
    }

    /**
     * Log an event for a specific item (e.g., partial loading).
     */
    public function logItemEvent(CargoItem $item, string $status, string $description, ?int $userId = null, ?string $location = null)
    {
        TrackingEvent::create([
            'consignment_id' => $item->consignment_id,
            'cargo_item_id' => $item->id,
            'event_type' => 'state_change',
            'to_status' => $status,
            'notes' => $description,
            'location_name' => $location,
            'performed_by_id' => $userId,
            'created_at' => now(),
        ]);

        $item->status = $status;
        
        if ($status === 'loaded') {
            $item->loaded_at = now();
        } elseif ($status === 'offloaded') {
            $item->offloaded_at = now();
        } elseif ($status === 'collected') {
            $item->collected_at = now();
        }

        $item->save();
    }
}
