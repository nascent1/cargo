<?php

namespace App\Services\Cargo;

use App\Models\Consignment;
use App\Models\CargoItem;
use App\Models\CargoLock;
use App\Models\CargoPayment;
use App\Models\SystemConfig;
use App\Models\TrackingEvent;
use Illuminate\Support\Facades\DB;
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
     *
     * $calculatedItems are lines from CargoFareCalculator (optionally merged with description,
     * declared value, vehicle details); totals and the space committed are derived from them.
     */
    public function confirmBooking(CargoLock $lock, array $consignmentData, array $calculatedItems, int $userId): Consignment
    {
        if (!$lock->isValid()) {
            throw new \Exception("Cargo lock has expired. Please restart the booking process.");
        }

        return DB::transaction(function () use ($lock, $consignmentData, $calculatedItems, $userId) {
            $items = collect($calculatedItems);
            $space = [
                'weight_kg' => round((float) $items->sum('weight_kg'), 2),
                'volume_cbm' => round((float) $items->sum('volume_cbm'), 4),
                'lane_meters' => round((float) $items->sum('lane_meters_consumed'), 2),
            ];

            $consignmentNumber = $this->newConsignmentNumber();
            $currency = $consignmentData['currency'] ?? 'TZS';

            // Create Consignment
            $consignment = Consignment::create([
                'uuid' => Str::uuid()->toString(),
                'consignment_number' => $consignmentNumber,
                'tracking_number' => $this->uniqueToken('tracking_number', fn () => strtoupper(Str::random(10))),
                'qr_code_token' => $this->uniqueToken('qr_code_token', fn () => 'CQ-' . strtoupper(Str::random(12))),
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
                'receiver_id_type' => $consignmentData['receiver_id_type'] ?? null,
                'receiver_id_number' => $consignmentData['receiver_id_number'] ?? null,
                'total_amount_tzs' => $currency === 'TZS' ? $consignmentData['net_amount'] : 0,
                'total_amount_usd' => $currency === 'USD' ? $consignmentData['net_amount'] : 0,
                'currency' => $currency,
                'discount_amount' => $consignmentData['total_discount'] ?? 0,
                'net_amount' => $consignmentData['net_amount'],
                'pricing_breakdown' => $consignmentData['breakdown'] ?? [],
                'total_items' => (int) $items->sum('quantity') ?: $items->count(),
                'total_weight_kg' => $space['weight_kg'],
                'total_volume_cbm' => $space['volume_cbm'],
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

            $itemPrefix = SystemConfig::getValue('cargo_item_prefix', 'CI');

            // Create Cargo Items
            foreach ($items->values() as $index => $item) {
                CargoItem::create([
                    'consignment_id' => $consignment->id,
                    'cargo_type_id' => $item['cargo_type_id'],
                    'item_number' => $consignmentNumber . '-' . str_pad($index + 1, 2, '0', STR_PAD_LEFT),
                    'description' => $item['description'] ?? null,
                    'quantity' => $item['quantity'] ?? 1,
                    'weight_kg' => $item['weight_kg'] ?? 0,
                    'length_m' => $item['length_m'] ?? null,
                    'width_m' => $item['width_m'] ?? null,
                    'height_m' => $item['height_m'] ?? null,
                    'volume_cbm' => $item['volume_cbm'] ?? 0,
                    'lane_meters_consumed' => $item['lane_meters_consumed'] ?? 0,
                    'vehicle_reg_number' => $item['vehicle_reg_number'] ?? null,
                    'driver_name' => $item['driver_name'] ?? null,
                    'driver_phone' => $item['driver_phone'] ?? null,
                    'is_fragile' => $item['is_fragile'] ?? false,
                    'declared_value' => $item['declared_value'] ?? null,
                    'fare_id' => $item['fare_id'] ?? null,
                    'fare_version' => $item['fare_version'] ?? null,
                    'pricing_method' => $item['pricing_method'] ?? 'flat',
                    // The line's own record of its price; reports never re-read the fare
                    'unit_rate' => $item['unit_rate'] ?? null,
                    'min_charge' => $item['min_charge'] ?? null,
                    'value_charge_pct' => $item['value_charge_pct'] ?? 0,
                    'priced_at' => $item['priced_at'] ?? now(),
                    'pricing_snapshot' => $item['pricing_snapshot'] ?? null,
                    'base_fare' => $item['base_fare'] ?? 0,
                    'surcharge_amount' => $item['surcharge_amount'] ?? 0,
                    'value_charge' => $item['value_charge'] ?? 0,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'final_fare' => $item['final_fare'] ?? 0,
                    'currency' => $currency,
                    'status' => 'booked',
                    'qr_code_token' => $this->uniqueItemToken($itemPrefix),
                ]);
            }

            // Commit only the space this booking uses (never more than was held)
            $this->inventoryService->commitLock($lock, $space);

            // Emit initial tracking event
            $this->trackingService->logEvent($consignment, 'pending_payment', 'Space reserved — awaiting payment', $userId);

            if ((float) ($consignmentData['total_discount'] ?? 0) > 0) {
                TrackingEvent::create([
                    'consignment_id' => $consignment->id,
                    'event_type' => 'fare_override',
                    'notes' => sprintf(
                        'Fare override: %s %s discount on %s. Reason: %s',
                        $currency, number_format((float) $consignmentData['total_discount']),
                        number_format((float) $consignmentData['net_amount'] + (float) $consignmentData['total_discount']),
                        $consignmentData['discount_reason'] ?? '—'
                    ),
                    'performed_by_id' => $userId,
                    'metadata' => ['discount' => (float) $consignmentData['total_discount'], 'reason' => $consignmentData['discount_reason'] ?? null],
                    'created_at' => now(),
                ]);
            }

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

            // Return exactly the space this booking committed
            $this->inventoryService->releaseUsed(
                $consignment->transport_service_id,
                (float) $consignment->total_weight_kg,
                (float) $consignment->total_volume_cbm,
                (float) $consignment->items()->sum('lane_meters_consumed'),
            );

            foreach ($consignment->items as $item) {
                $item->status = 'cancelled';
                $item->save();
            }

            $this->trackingService->logEvent($consignment, 'cancelled', 'Booking cancelled' . ($reason ? ". Reason: {$reason}" : ''), $userId);
        });

        // Tell the sender when the booking had been paid (an unpaid hold that lapses is not texted)
        if ($consignment->payment_status === 'paid') {
            app(\App\Services\SmsNotifier::class)->sendAfterCommit('cargo_cancelled', $consignment->sender_phone,
                app(\App\Services\SmsNotifier::class)->bookingData($consignment) + ['reason' => $reason ?: '—']);
        }
    }

    /**
     * CG-261006-7KQ2M — prefix, date, 5 unambiguous characters.
     */
    private function newConsignmentNumber(): string
    {
        $prefix = SystemConfig::getValue('cargo_consignment_prefix', 'CG');

        return $this->uniqueToken('consignment_number', fn () => $prefix . '-' . now()->format('ymd') . '-' . $this->randomCode(5));
    }

    private function uniqueToken(string $column, \Closure $make): string
    {
        for ($i = 0; $i < 10; $i++) {
            $value = $make();
            if (!Consignment::withTrashed()->where($column, $value)->exists()) {
                return $value;
            }
        }

        throw new \RuntimeException("Could not generate a unique {$column}.");
    }

    private function uniqueItemToken(string $prefix): string
    {
        do {
            $token = $prefix . strtoupper(Str::random(12));
        } while (CargoItem::where('qr_code_token', $token)->exists());

        return $token;
    }

    private function randomCode(int $length): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

        return collect(range(1, $length))->map(fn () => $alphabet[random_int(0, strlen($alphabet) - 1)])->implode('');
    }
}
