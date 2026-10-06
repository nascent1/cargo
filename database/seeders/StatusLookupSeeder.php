<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
// use App\Models\BatchStatus;
use App\Models\OrderStatus;
use App\Models\PackagingStatus;
use App\Models\PaymentStatus;
use App\Models\ShipmentStatus;
use App\Models\InventoryMovementType;
use App\Models\InspectionStatus;

class StatusLookupSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Batch Statuses
       

        // 2. Order Statuses
        $orderStatuses = [
            ['code' => 'pending_payment', 'label' => 'Pending Payment', 'color_badge' => 'bg-yellow-100 text-yellow-800', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'payment_processing', 'label' => 'Payment Processing', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'confirmed', 'label' => 'Confirmed Order', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 30, 'is_terminal' => false],
            ['code' => 'picking', 'label' => 'Picking from Warehouse', 'color_badge' => 'bg-purple-100 text-purple-800', 'sort_order' => 40, 'is_terminal' => false],
            ['code' => 'packing', 'label' => 'Packing Goods', 'color_badge' => 'bg-indigo-100 text-indigo-800', 'sort_order' => 50, 'is_terminal' => false],
            ['code' => 'shipped', 'label' => 'Shipped / Dispatched', 'color_badge' => 'bg-cyan-100 text-cyan-800', 'sort_order' => 60, 'is_terminal' => false],
            ['code' => 'in_transit', 'label' => 'In Transit', 'color_badge' => 'bg-teal-100 text-teal-800', 'sort_order' => 70, 'is_terminal' => false],
            ['code' => 'delivered', 'label' => 'Delivered', 'color_badge' => 'bg-green-100 text-green-800', 'sort_order' => 80, 'is_terminal' => true],
            ['code' => 'cancelled', 'label' => 'Cancelled', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 90, 'is_terminal' => true],
            ['code' => 'refunded', 'label' => 'Refunded', 'color_badge' => 'bg-gray-100 text-gray-800', 'sort_order' => 100, 'is_terminal' => true],
        ];

        foreach ($orderStatuses as $status) {
            OrderStatus::updateOrCreate(['code' => $status['code']], $status);
        }

        // 3. Packaging Statuses
        $packagingStatuses = [
            ['code' => 'planned', 'label' => 'Planned Run', 'color_badge' => 'bg-yellow-100 text-yellow-800', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'materials_staged', 'label' => 'Materials Staged', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'in_progress', 'label' => 'Packaging In Progress', 'color_badge' => 'bg-purple-100 text-purple-800', 'sort_order' => 30, 'is_terminal' => false],
            ['code' => 'quality_check', 'label' => 'Final QC Check', 'color_badge' => 'bg-amber-100 text-amber-800', 'sort_order' => 40, 'is_terminal' => false],
            ['code' => 'completed', 'label' => 'Completed Run', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 50, 'is_terminal' => true],
            ['code' => 'quarantined', 'label' => 'Quarantined', 'color_badge' => 'bg-orange-100 text-orange-800', 'sort_order' => 60, 'is_terminal' => false],
            ['code' => 'rejected', 'label' => 'Rejected Run', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 70, 'is_terminal' => true],
        ];

        foreach ($packagingStatuses as $status) {
            PackagingStatus::updateOrCreate(['code' => $status['code']], $status);
        }

        // 4. Payment Statuses
        $paymentStatuses = [
            ['code' => 'pending', 'label' => 'Pending', 'color_badge' => 'bg-yellow-100 text-yellow-800', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'processing', 'label' => 'Processing', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'completed', 'label' => 'Completed', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 30, 'is_terminal' => true],
            ['code' => 'failed', 'label' => 'Failed', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 40, 'is_terminal' => true],
            ['code' => 'refunded', 'label' => 'Refunded', 'color_badge' => 'bg-gray-100 text-gray-800', 'sort_order' => 50, 'is_terminal' => true],
            ['code' => 'partially_refunded', 'label' => 'Partially Refunded', 'color_badge' => 'bg-orange-100 text-orange-800', 'sort_order' => 60, 'is_terminal' => false],
        ];

        foreach ($paymentStatuses as $status) {
            PaymentStatus::updateOrCreate(['code' => $status['code']], $status);
        }

        // 5. Shipment Statuses
        $shipmentStatuses = [
            ['code' => 'pending', 'label' => 'Pending Dispatch', 'color_badge' => 'bg-yellow-100 text-yellow-800', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'dispatched', 'label' => 'Dispatched', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'in_transit', 'label' => 'In Transit', 'color_badge' => 'bg-purple-100 text-purple-800', 'sort_order' => 30, 'is_terminal' => false],
            ['code' => 'out_for_delivery', 'label' => 'Out For Delivery', 'color_badge' => 'bg-cyan-100 text-cyan-800', 'sort_order' => 40, 'is_terminal' => false],
            ['code' => 'delivered', 'label' => 'Delivered', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 50, 'is_terminal' => true],
            ['code' => 'failed_delivery', 'label' => 'Failed Delivery Attempt', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 60, 'is_terminal' => false],
            ['code' => 'returned', 'label' => 'Returned to Warehouse', 'color_badge' => 'bg-gray-100 text-gray-800', 'sort_order' => 70, 'is_terminal' => true],
        ];

        foreach ($shipmentStatuses as $status) {
            ShipmentStatus::updateOrCreate(['code' => $status['code']], $status);
        }

        // 6. Inventory Movement Types
        $movementTypes = [
            ['code' => 'harvest_in', 'label' => 'Harvest Intake (GRN)', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 10],
            ['code' => 'sale_out', 'label' => 'Customer Order Fulfillment', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20],
            ['code' => 'transfer_out', 'label' => 'Warehouse Transfer Out', 'color_badge' => 'bg-purple-100 text-purple-800', 'sort_order' => 30],
            ['code' => 'transfer_in', 'label' => 'Warehouse Transfer In', 'color_badge' => 'bg-teal-100 text-teal-800', 'sort_order' => 40],
            ['code' => 'return', 'label' => 'Customer Return Intake', 'color_badge' => 'bg-amber-100 text-amber-800', 'sort_order' => 50],
            ['code' => 'damage', 'label' => 'Damaged Stock Report', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 60],
            ['code' => 'adjustment', 'label' => 'Stock Audit Adjustment', 'color_badge' => 'bg-gray-100 text-gray-800', 'sort_order' => 70],
            ['code' => 'write_off', 'label' => 'Stock Write-Off', 'color_badge' => 'bg-red-200 text-red-900', 'sort_order' => 80],
            ['code' => 'reservation', 'label' => 'Stock Hold / Reservation', 'color_badge' => 'bg-indigo-100 text-indigo-800', 'sort_order' => 90],
            ['code' => 'reservation_release', 'label' => 'Reservation Released', 'color_badge' => 'bg-cyan-100 text-cyan-800', 'sort_order' => 100],
            ['code' => 'expiry_write_off', 'label' => 'Expired Stock Write-Off', 'color_badge' => 'bg-orange-100 text-orange-800', 'sort_order' => 110],
        ];

        foreach ($movementTypes as $type) {
            InventoryMovementType::updateOrCreate(['code' => $type['code']], $type);
        }

        // 7. Inspection Statuses
        $inspectionStatuses = [
            ['code' => 'pending', 'label' => 'Pending Inspection', 'color_badge' => 'bg-yellow-100 text-yellow-800', 'sort_order' => 10, 'is_terminal' => false],
            ['code' => 'in_progress', 'label' => 'Inspection In Progress', 'color_badge' => 'bg-blue-100 text-blue-800', 'sort_order' => 20, 'is_terminal' => false],
            ['code' => 'passed', 'label' => 'Passed Inspection', 'color_badge' => 'bg-emerald-100 text-emerald-800', 'sort_order' => 30, 'is_terminal' => true],
            ['code' => 'failed', 'label' => 'Failed Inspection', 'color_badge' => 'bg-red-100 text-red-800', 'sort_order' => 40, 'is_terminal' => true],
            ['code' => 'requires_retest', 'label' => 'Requires Re-testing', 'color_badge' => 'bg-amber-100 text-amber-800', 'sort_order' => 50, 'is_terminal' => false],
        ];

        foreach ($inspectionStatuses as $status) {
            InspectionStatus::updateOrCreate(['code' => $status['code']], $status);
        }
    }
}
