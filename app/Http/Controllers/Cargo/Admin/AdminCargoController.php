<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Consignment;
use App\Models\CargoType;
use App\Models\CargoFare;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\TransportService;
use App\Models\PaymentMethod;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoTrackingService;
use App\Services\Cargo\CargoPaymentService;
use App\Exceptions\CargoPaymentException;
use Illuminate\Validation\Rule;

class AdminCargoController extends Controller
{
    public function dashboardStats()
    {
        $today = now()->startOfDay();
        
        $consignmentsToday = Consignment::where('created_at', '>=', $today)->count();
        $revenueToday = Consignment::where('created_at', '>=', $today)
            ->whereNotIn('status', ['cancelled'])
            ->sum('total_amount_tzs');
            
        $activeShipments = Consignment::whereIn('status', ['booked', 'loaded', 'in_transit'])->count();
        
        $services = TransportService::with(['transportUnit.cargoCapacities', 'cargoInventory', 'route'])
            ->whereDate('departure_date', today()->toDateString())
            ->get();

        $capacityOverview = $services->map(function ($v) {
            $cap = $v->transportUnit?->cargoCapacities->first();
            $inv = $v->cargoInventory;
            return [
                'service_id' => $v->id,
                'ferry' => $v->transportUnit?->name,
                'route' => $v->route?->name,
                'departure' => $v->departure_time,
                'lane_meters' => [
                    'total' => $cap?->total_lane_meters ?? 0,
                    'used' => $inv ? ($cap?->total_lane_meters ?? 0) - ($inv->available_lane_meters ?? 0) : 0,
                    'available' => $inv?->available_lane_meters ?? ($cap?->total_lane_meters ?? 0),
                ],
                'weight_kg' => [
                    'total' => $cap?->total_weight_capacity_kg ?? 0,
                    'used' => $inv ? ($cap?->total_weight_capacity_kg ?? 0) - ($inv->available_weight ?? 0) : 0,
                    'available' => $inv?->available_weight ?? ($cap?->total_weight_capacity_kg ?? 0),
                ],
                'volume_cbm' => [
                    'total' => $cap?->total_volume_capacity_cbm ?? 0,
                    'used' => $inv ? ($cap?->total_volume_capacity_cbm ?? 0) - ($inv->available_volume ?? 0) : 0,
                    'available' => $inv?->available_volume ?? ($cap?->total_volume_capacity_cbm ?? 0),
                ],
            ];
        })->toArray();

        return response()->json([
            'consignments_today' => $consignmentsToday,
            'revenue_tzs_today' => $revenueToday,
            'active_shipments' => $activeShipments,
            'capacityOverview' => $capacityOverview,
        ]);
    }

    public function getConsignments(Request $request)
    {
        $query = Consignment::with(['route.origin', 'route.destination', 'bookedByUser', 'bookedByAgent']);
        
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('consignment_number', 'like', "%$search%")
                  ->orWhere('sender_name', 'like', "%$search%")
                  ->orWhere('receiver_name', 'like', "%$search%");
            });
        }
        
        return response()->json($query->orderBy('created_at', 'desc')->paginate(20));
    }

    public function getCargoTypes()
    {
        return response()->json(CargoType::all());
    }

    public function storeCargoType(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'code' => 'required|string|unique:cargo_types',
            'category' => 'required|string',
            'measurement_unit' => 'required|in:kg,cbm,unit,lane_meter',
            'lane_meters' => 'required|numeric',
            'max_weight_kg' => 'required|numeric',
            'requires_hazmat_declaration' => 'boolean',
            'is_fragile' => 'boolean',
            'icon' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        $type = CargoType::create($validated);
        return response()->json($type, 201);
    }

    public function updateCargoType(Request $request, CargoType $cargoType)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'category' => 'required|string',
            'measurement_unit' => 'required|in:kg,cbm,unit,lane_meter',
            'lane_meters' => 'required|numeric',
            'max_weight_kg' => 'required|numeric',
            'requires_hazmat_declaration' => 'boolean',
            'is_fragile' => 'boolean',
            'icon' => 'nullable|string',
            'status' => 'required|in:active,inactive'
        ]);

        $cargoType->update($validated);
        return response()->json($cargoType);
    }

    // ── Cargo Fares CRUD ──────────────────────────────────

    public function getCargoFares(Request $request)
    {
        $query = CargoFare::with(['cargoType', 'route', 'transportUnit']);

        if ($request->has('cargo_type_id')) {
            $query->where('cargo_type_id', $request->cargo_type_id);
        }
        if ($request->has('route_id')) {
            $query->where('route_id', $request->route_id);
        }

        return response()->json($query->orderBy('cargo_type_id')->get());
    }

    public function storeCargoFare(Request $request)
    {
        $validated = $request->validate([
            'cargo_type_id' => 'required|exists:cargo_types,id',
            'route_id' => 'nullable|exists:routes,id',
            'transport_unit_id' => 'nullable|exists:transport_units,id',
            'price_tzs' => 'required|numeric|min:0',
            'price_usd' => 'required|numeric|min:0',
            'price_per_kg_tzs' => 'nullable|numeric|min:0',
            'price_per_kg_usd' => 'nullable|numeric|min:0',
            'price_per_cbm_tzs' => 'nullable|numeric|min:0',
            'price_per_cbm_usd' => 'nullable|numeric|min:0',
            'min_charge_tzs' => 'nullable|numeric|min:0',
            'min_charge_usd' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $fare = CargoFare::create($validated);
        return response()->json($fare->load(['cargoType', 'route', 'transportUnit']), 201);
    }

    public function updateCargoFare(Request $request, CargoFare $cargoFare)
    {
        $validated = $request->validate([
            'cargo_type_id' => 'required|exists:cargo_types,id',
            'route_id' => 'nullable|exists:routes,id',
            'transport_unit_id' => 'nullable|exists:transport_units,id',
            'price_tzs' => 'required|numeric|min:0',
            'price_usd' => 'required|numeric|min:0',
            'price_per_kg_tzs' => 'nullable|numeric|min:0',
            'price_per_kg_usd' => 'nullable|numeric|min:0',
            'price_per_cbm_tzs' => 'nullable|numeric|min:0',
            'price_per_cbm_usd' => 'nullable|numeric|min:0',
            'min_charge_tzs' => 'nullable|numeric|min:0',
            'min_charge_usd' => 'nullable|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $cargoFare->update($validated);
        return response()->json($cargoFare->load(['cargoType', 'route', 'transportUnit']));
    }

    public function destroyCargoFare(CargoFare $cargoFare)
    {
        $cargoFare->delete();
        return response()->json(['message' => 'Fare deleted successfully']);
    }

    // ── Lookup Helpers ────────────────────────────────────────

    public function getRoutes()
    {
        $routes = Route::with(['origin', 'destination'])
            ->where('status', 'active')
            ->orderBy('name')
            ->get()

            ->map(fn($r) => [
                'id'   => $r->id,
                'name' => $r->name ?? (($r->origin->name ?? '?') . ' → ' . ($r->destination->name ?? '?')),
            ]);
        return response()->json($routes);
    }

    public function getTransportUnits()
    {
        $units = TransportUnit::where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'registration_number', 'type']);
        return response()->json($units);
    }

    public function getServices()
    {
        $services = TransportService::with(['route', 'transportUnit'])
            ->orderBy('departure_date', 'desc')
            ->orderBy('departure_time', 'desc')
            ->limit(100)
            ->get();
        return response()->json($services);
    }

    public function destroyCargoType(CargoType $cargoType)
    {
        // Prevent deletion if fares exist
        if ($cargoType->fares()->count() > 0) {
            return response()->json(['message' => 'Cannot delete type with existing fares.'], 422);
        }
        $cargoType->delete();
        return response()->json(['message' => 'Cargo type deleted.']);
    }

    // ── Single Consignment Detail ─────────────────────────

    public function showConsignment(Consignment $consignment)
    {
        $consignment->load([
            'items.cargoType',
            'trackingEvents' => function ($q) {
                $q->orderBy('created_at', 'desc');
            },
            'route.origin',
            'route.destination',
            'transportService.transportUnit',
            'bookedByUser',
            'bookedByAgent',
            'receipts',
        ]);

        return response()->json($consignment);
    }



    // ═══════════════════════════════════════════════════════════
    // ADMIN CARGO BOOKING (Walk-in counter flow)
    // ═══════════════════════════════════════════════════════════

    public function bookingTerminal()
    {
        return view('admin.cargo.booking', ['paymentMethods' => PaymentMethod::activeOptions()]);
    }

    public function getAvailableServicesForBooking(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $services = \App\Models\TransportService::with(['route.origin', 'route.destination', 'transportUnit', 'cargoInventory'])
            ->where('departure_date', $request->date)
            ->where('status', 'scheduled')
            ->orderBy('departure_time')
            ->get();

        return response()->json($services);
    }

    public function calculateFareAdmin(Request $request)
    {
        $request->validate([
            'cargo_type_id' => 'required|exists:cargo_types,id',
            'route_id' => 'required|exists:routes,id',
            'weight_kg' => 'nullable|numeric|min:0',
            'volume_cbm' => 'nullable|numeric|min:0',
            'quantity' => 'nullable|integer|min:1',
        ]);

        $type = CargoType::findOrFail($request->cargo_type_id);
        $fare = CargoFare::where('cargo_type_id', $type->id)
            ->where('route_id', $request->route_id)
            ->where('status', 'active')
            ->first();

        if (!$fare) {
            // Fallback: route-agnostic fare
            $fare = CargoFare::where('cargo_type_id', $type->id)
                ->whereNull('route_id')
                ->where('status', 'active')
                ->first();
        }

        if (!$fare) {
            return response()->json(['message' => 'No fare configured for this cargo type and route.'], 404);
        }

        $baseFare = 0;
        $quantity = $request->quantity ?? 1;

        switch ($type->measurement_unit) {
            case 'kg':
                $weight = $request->weight_kg ?? 0;
                $baseFare = max($fare->price_per_kg_tzs * $weight, $fare->min_charge_tzs ?? 0);
                break;
            case 'cbm':
                $volume = $request->volume_cbm ?? 0;
                $baseFare = max($fare->price_per_cbm_tzs * $volume, $fare->min_charge_tzs ?? 0);
                break;
            case 'lane_meter':
                $baseFare = $fare->price_tzs * $quantity;
                break;
            case 'unit':
            default:
                $baseFare = $fare->price_tzs * $quantity;
                break;
        }

        $surcharges = 0;
        $surchargeDetails = [];

        if ($type->is_fragile) {
            $fragileSurcharge = $baseFare * 0.05;
            $surcharges += $fragileSurcharge;
            $surchargeDetails[] = ['label' => 'Fragile handling (5%)', 'amount' => $fragileSurcharge];
        }
        if ($type->requires_hazmat_declaration) {
            $hazmatFee = 10000;
            $surcharges += $hazmatFee;
            $surchargeDetails[] = ['label' => 'Hazmat declaration', 'amount' => $hazmatFee];
        }

        return response()->json([
            'base_fare' => round($baseFare),
            'surcharges' => round($surcharges),
            'surcharge_details' => $surchargeDetails,
            'total_fare' => round($baseFare + $surcharges),
            'currency' => 'TZS',
            'fare_id' => $fare->id,
        ]);
    }

    public function acquireLockAdmin(Request $request, \App\Services\Cargo\CargoInventoryService $inventorySvc)
    {
        $request->validate([
            'transport_service_id' => 'required|exists:transport_services,id',
            'items' => 'required|array',
        ]);

        try {
            $lock = $inventorySvc->acquireLock(
                $request->transport_service_id,
                $request->items,
                $request->user()->id,
                $request->user()->agent_id ?? null
            );
            return response()->json(['lock_id' => $lock->id, 'expires_at' => $lock->expires_at]);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function releaseLockAdmin(Request $request, \App\Services\Cargo\CargoInventoryService $inventorySvc)
    {
        $request->validate(['lock_id' => 'required|exists:cargo_locks,id']);

        try {
            $lock = \App\Models\CargoLock::findOrFail($request->lock_id);
            $inventorySvc->releaseLock($lock);
            return response()->json(['message' => 'Lock released']);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function confirmBookingAdmin(Request $request, \App\Services\Cargo\CargoInventoryService $inventorySvc, CargoPaymentService $paymentSvc)
    {
        $validated = $request->validate([
            'lock_id' => 'required|exists:cargo_locks,id',
            'transport_service_id' => 'required|exists:transport_services,id',
            'route_id' => 'required|exists:routes,id',
            'sender_name' => 'required|string|max:100',
            'sender_phone' => 'required|string|max:20',
            'sender_id_type' => 'nullable|string|max:30',
            'sender_id_number' => 'nullable|string|max:50',
            'receiver_name' => 'required|string|max:100',
            'receiver_phone' => 'required|string|max:20',
            'payment_method' => ['required', Rule::exists('payment_methods', 'slug')->where('is_active', true)],
            'payer_msisdn' => 'nullable|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.cargo_type_id' => 'required|exists:cargo_types,id',
            'items.*.description' => 'nullable|string',
            'items.*.weight_kg' => 'nullable|numeric|min:0',
            'items.*.length_m' => 'nullable|numeric|min:0',
            'items.*.width_m' => 'nullable|numeric|min:0',
            'items.*.height_m' => 'nullable|numeric|min:0',
            'items.*.quantity' => 'nullable|integer|min:1',
            'items.*.fare_amount' => 'required|numeric|min:0',
        ]);

        // Reject an unusable channel / payer number before anything is booked
        $method = PaymentMethod::where('slug', $validated['payment_method'])->firstOrFail();
        try {
            $paymentSvc->assertCanStart($method, $validated['payer_msisdn'] ?? null);
        } catch (CargoPaymentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return \DB::transaction(function () use ($validated, $request, $inventorySvc, $paymentSvc, $method) {
            $lock = \App\Models\CargoLock::findOrFail($validated['lock_id']);
            $totalAmount = collect($validated['items'])->sum('fare_amount');

            // Generate consignment number
            $date = now()->format('ymd');
            $seq = Consignment::whereDate('created_at', now())->count() + 1;
            $consignmentNumber = 'CG-' . $date . '-' . str_pad($seq, 4, '0', STR_PAD_LEFT);

            $consignment = Consignment::create([
                'uuid' => \Str::uuid()->toString(),
                'consignment_number' => $consignmentNumber,
                'tracking_number' => 'TRK-' . strtoupper(\Str::random(10)),
                'qr_code_token' => bin2hex(random_bytes(16)),
                'transport_service_id' => $validated['transport_service_id'],
                'route_id' => $validated['route_id'],
                'sender_name' => $validated['sender_name'],
                'sender_phone' => $validated['sender_phone'],
                'sender_id_type' => $validated['sender_id_type'] ?? null,
                'sender_id_number' => $validated['sender_id_number'] ?? null,
                'receiver_name' => $validated['receiver_name'],
                'receiver_phone' => $validated['receiver_phone'],
                'total_amount_tzs' => $totalAmount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'unpaid',
                'status' => 'pending_payment', // becomes 'booked' once CargoPaymentService confirms payment
                'booked_by_user_id' => $request->user()->id,
            ]);

            // Create cargo items
            foreach ($validated['items'] as $i => $item) {
                $volume = null;
                if (!empty($item['length_m']) && !empty($item['width_m']) && !empty($item['height_m'])) {
                    $volume = $item['length_m'] * $item['width_m'] * $item['height_m'];
                }

                \App\Models\CargoItem::create([
                    'consignment_id' => $consignment->id,
                    'cargo_type_id' => $item['cargo_type_id'],
                    'description' => $item['description'] ?? null,
                    'weight_kg' => $item['weight_kg'] ?? null,
                    'length_m' => $item['length_m'] ?? null,
                    'width_m' => $item['width_m'] ?? null,
                    'height_m' => $item['height_m'] ?? null,
                    'volume_cbm' => $volume,
                    'quantity' => $item['quantity'] ?? 1,
                    'final_fare' => $item['fare_amount'],
                    'currency' => 'TZS',
                    'status' => 'accepted',
                    'item_number' => $consignment->consignment_number . '-' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                    'qr_code_token' => bin2hex(random_bytes(16)),
                ]);
            }

            // Commit cargo inventory lock
            $inventorySvc->commitLock($lock);

            // Create initial tracking event
            if (class_exists(\App\Models\TrackingEvent::class)) {
                \App\Models\TrackingEvent::create([
                    'consignment_id' => $consignment->id,
                    'event_type' => 'state_change',
                    'to_status' => 'pending_payment',
                    'notes' => 'Cargo accepted at counter — awaiting payment',
                    'performed_by_id' => $request->user()->id,
                ]);
            }

            // Cash completes immediately; push / Lipa Namba complete from the booking profile
            $payment = $paymentSvc->start($consignment, $method, $validated['payer_msisdn'] ?? null, $request->user());

            return response()->json([
                'message' => $payment->status === 'paid' ? 'Booking confirmed and paid.' : 'Booking reserved — awaiting payment.',
                'consignment' => $consignment->fresh()->load('items.cargoType'),
                'payment' => $payment->toApi(),
            ], 201);
        });
    }

    // ═══════════════════════════════════════════════════════════
    // BOOKING PROFILE (Full-page Blade view)
    // ═══════════════════════════════════════════════════════════

    public function showBookingProfile(Consignment $consignment)
    {
        $consignment->load([
            'items.cargoType',
            // Consignment-level events only; item-level rows duplicate them
            'trackingEvents' => fn ($q) => $q->whereNull('cargo_item_id')->orderByDesc('created_at')->orderByDesc('id'),
            'route.origin',
            'route.destination',
            'transportService.transportUnit',
            'bookedByUser',
            'bookedByAgent',
            'receipts.issuedBy',
            'paymentChannel',
            'payments' => fn ($q) => $q->orderByDesc('id'),
            'payments.paymentMethod',
            'payments.confirmedBy',
            'payments.initiatedBy',
        ]);

        return view('admin.cargo.bookings.show', compact('consignment'));
    }

    public function cancelConsignment(Request $request, Consignment $consignment, CargoBookingService $bookingSvc)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        try {
            // Shared with the sales portal: releases inventory, cancels items, logs tracking
            $bookingSvc->cancelBooking($consignment, auth()->id(), $request->reason);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.cargo.bookings.show', $consignment)
            ->with('success', 'Booking has been cancelled successfully.');
    }

    public function updateConsignmentStatus(Request $request, Consignment $consignment, CargoTrackingService $trackingSvc)
    {
        $request->validate([
            'status' => 'required|in:booked,loaded,in_transit,delivered,collected',
        ]);

        $newStatus = $request->status;
        $oldStatus = $consignment->status;

        if ($oldStatus === 'cancelled') {
            return redirect()->back()->with('error', 'Cannot update status of a cancelled booking.');
        }
        if ($oldStatus === 'pending_payment') {
            return redirect()->back()->with('error', 'This booking is awaiting payment. Complete the payment before updating its status.');
        }

        // Updates consignment + item statuses, timestamps and tracking events
        $trackingSvc->logEvent($consignment, $newStatus, "Status updated from {$oldStatus} to {$newStatus}", auth()->id());

        return redirect()->route('admin.cargo.bookings.show', $consignment)
            ->with('success', "Booking status updated to " . ucfirst(str_replace('_', ' ', $newStatus)) . ".");
    }

    public function printReceipt(Consignment $consignment)
    {
        abort_if($consignment->isAwaitingPayment(), 409, 'Payment pending — the receipt is available once the booking is paid.');

        $consignment->load([
            'items.cargoType',
            'route.origin',
            'route.destination',
            'transportService.transportUnit',
            'bookedByUser',
            'bookedByAgent',
            'receipts',
            'paymentChannel',
        ]);

        return view('cargo.receipt', compact('consignment'));
    }
}
