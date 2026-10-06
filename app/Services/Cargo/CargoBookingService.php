<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\CargoItem;
use App\Models\CargoInventory;
use App\Models\CargoLock;
use App\Models\CargoPayment;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CargoBookingService
{
    protected $inventoryService;
    protected $trackingService;

    public function __construct(CargoInventoryService $inventoryService, CargoTrackingService $trackingService)
    {
        $this->inventoryService = $inventoryService;
        $this->trackingService = $trackingService;
    }

    /**
     * Confirm a booking from a locked state.
     */
    public function confirmBooking(CargoLock $lock, array $consignmentData, array $calculatedItems, int $userId): Consignment
    {
        if (!$lock->isValid()) {
            throw new \Exception("Cargo lock has expired. Please restart the booking process.");
        }

        return DB::transaction(function () use ($lock, $consignmentData, $calculatedItems, $userId) {
            
            $prefix = $this->getConfig('cargo_consignment_prefix', 'CG');
            $consignmentNumber = $prefix . date('ymd') . strtoupper(Str::random(5));
            $trackingNumber = strtoupper(Str::random(10));
            $consignmentQr = 'CQ-' . strtoupper(Str::random(12));

            // Create Consignment
            $consignment = Consignment::create([
                'uuid' => Str::uuid()->toString(),
                'consignment_number' => $consignmentNumber,
                'tracking_number' => $trackingNumber,
                'qr_code_token' => $consignmentQr,
                'transport_service_id' => $lock->transport_service_id,
                'route_id' => $consignmentData['route_id'],
                'sender_name' => $consignmentData['sender_name'],
                'sender_phone' => $consignmentData['sender_phone'],
                'sender_email' => $consignmentData['sender_email'] ?? null,
                'sender_id_type' => $consignmentData['sender_id_type'] ?? null,
                'sender_id_number' => $consignmentData['sender_id_number'] ?? null,
                'receiver_name' => $consignmentData['receiver_name'],
                'receiver_phone' => $consignmentData['receiver_phone'],
                'receiver_email' => $consignmentData['receiver_email'] ?? null,
                'total_amount_tzs' => $consignmentData['currency'] === 'TZS' ? $consignmentData['net_amount'] : 0,
                'total_amount_usd' => $consignmentData['currency'] === 'USD' ? $consignmentData['net_amount'] : 0,
                'discount_amount' => $consignmentData['total_discount'] ?? 0,
                'net_amount' => $consignmentData['net_amount'],
                'pricing_breakdown' => $consignmentData['breakdown'] ?? [],
                'total_weight_kg' => $lock->weight_kg,
                'total_volume_cbm' => $lock->volume_cbm,
                'has_hazmat' => $consignmentData['has_hazmat'] ?? false,
                'has_fragile' => $consignmentData['has_fragile'] ?? false,
                'has_livestock' => $consignmentData['has_livestock'] ?? false,
                'status' => 'pending_payment', // becomes 'booked' once CargoPaymentService confirms payment
                'booked_by_user_id' => $userId,
                'booked_by_agent_id' => $lock->agent_id,
                'payment_method' => $consignmentData['payment_method'] ?? null,
                'payment_status' => 'unpaid',
                'special_instructions' => $consignmentData['special_instructions'] ?? null,
            ]);

            $itemPrefix = $this->getConfig('cargo_item_prefix', 'CI');

            // Create Cargo Items
            foreach ($calculatedItems as $index => $item) {
                $cargoItem = CargoItem::create([
                    'consignment_id' => $consignment->id,
                    'cargo_type_id' => $item['cargo_type_id'],
                    'item_number' => $consignmentNumber . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'description' => $item['description'] ?? 'Cargo Item',
                    'weight_kg' => $item['weight_kg'] ?? 0,
                    'length_m' => $item['length_m'] ?? null,
                    'width_m' => $item['width_m'] ?? null,
                    'height_m' => $item['height_m'] ?? null,
                    'volume_cbm' => $item['volume_cbm'] ?? 0,
                    'lane_meters_consumed' => $item['lane_meters_consumed'] ?? 0,
                    'is_fragile' => $item['is_fragile'] ?? false,
                    'declared_value' => $item['declared_value'] ?? 0,
                    'fare_id' => $item['fare_id'] ?? null,
                    'base_fare' => $item['base_fare'] ?? 0,
                    'surcharge_amount' => $item['surcharge_amount'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'final_fare' => $item['final_fare'] ?? 0,
                    'status' => 'booked',
                    'qr_code_token' => $itemPrefix . strtoupper(Str::random(12)),
                ]);
            }

            // Commit Inventory Lock
            $this->inventoryService->commitLock($lock);

            // Emit initial tracking event
            $this->trackingService->logEvent($consignment, 'pending_payment', 'Space reserved — awaiting payment', $userId);

            return $consignment;
        });
    }

    public function cancelBooking(Consignment $consignment, ?int $userId, ?string $reason = null)
    {
        if (!$consignment->isCancellable()) {
            throw new \Exception("This booking cannot be cancelled in its current state ({$consignment->status}).");
        }

        DB::transaction(function () use ($consignment, $userId, $reason) {
            $consignment->status = 'cancelled';
            $consignment->cancelled_at = now();
            $consignment->cancellation_reason = $reason;
            $consignment->save();

            // Close any payment still waiting on the customer
            CargoPayment::where('consignment_id', $consignment->id)->open()
                ->update(['status' => 'cancelled', 'failure_reason' => 'Booking cancelled.', 'updated_at' => now()]);

            // Reverse inventory
            $inventory = CargoInventory::where('transport_service_id', $consignment->transport_service_id)->lockForUpdate()->first();
            if ($inventory) {
                $inventory->used_weight_kg = max(0, $inventory->used_weight_kg - $consignment->total_weight_kg);
                $inventory->used_volume_cbm = max(0, $inventory->used_volume_cbm - $consignment->total_volume_cbm);
                // Lane meters reversal would require iterating items to sum lane_meters_consumed
                $laneMeters = $consignment->items()->sum('lane_meters_consumed');
                $inventory->used_lane_meters = max(0, $inventory->used_lane_meters - $laneMeters);
                $inventory->save();
            }

            foreach ($consignment->items as $item) {
                $item->status = 'cancelled';
                $item->save();
            }

            $this->trackingService->logEvent($consignment, 'cancelled', 'Booking cancelled' . ($reason ? ". Reason: {$reason}" : ''), $userId);
        });
    }

    private function getConfig(string $key, string $default): string
    {
        return Cache::remember("sys_cfg_{$key}", 3600, function() use ($key, $default) {
            $cfg = SystemConfig::where('key_name', $key)->first();
            return $cfg ? $cfg->value : $default;
        });
    }
}
