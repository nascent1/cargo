<?php

namespace App\Http\Controllers\Cargo\Admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Cargo\Concerns\BuildsCargoCart;
use App\Http\Controllers\Concerns\SubmitsDualControl;
use Illuminate\Http\Request;
use App\Models\Consignment;
use App\Models\CargoType;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\TransportService;
use App\Models\PaymentMethod;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoDashboardService;
use App\Services\Cargo\CargoManifestService;

class AdminCargoController extends Controller
{
    use BuildsCargoCart, SubmitsDualControl;

    // ═══════════════════════════════════════════════════════════
    // DASHBOARD
    // ═══════════════════════════════════════════════════════════

    public function dashboard()
    {
        return view('admin.cargo.dashboard', ['periods' => CargoDashboardService::PERIODS]);
    }

    public function dashboardStats(Request $request, CargoDashboardService $dashboard)
    {
        $period = array_key_exists($request->period, CargoDashboardService::PERIODS) ? $request->period : 'today';

        return response()->json($dashboard->summary($period));
    }

    // ═══════════════════════════════════════════════════════════
    // CONSIGNMENTS LIST (filters shared by the page, its API and the Excel export)
    // ═══════════════════════════════════════════════════════════

    private const LIST_STATUSES = ['pending_payment', 'booked', 'loaded', 'in_transit', 'delivered', 'unclaimed', 'collected', 'cancelled'];

    public function consignmentsPage()
    {
        return view('admin.cargo.consignments', [
            'statuses' => self::LIST_STATUSES,
            'routes' => Route::orderBy('name')->get(['id', 'name']),
            'paymentMethods' => PaymentMethod::orderBy('sort_order')->get(['slug', 'name']),
            'bookers' => \App\Models\User::whereIn('id', Consignment::select('booked_by_user_id')->whereNotNull('booked_by_user_id')->distinct())
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'canExport' => auth()->user()->hasPermission('cargo-export-report'),
        ]);
    }

    public function getConsignments(Request $request)
    {
        $perPage = min(max((int) $request->input('per_page', 20), 5), 100);

        $page = $this->consignmentQuery($request)
            ->with(['route:id,name', 'transportService:id,departure_date,departure_time,service_number,transport_unit_id', 'transportService.transportUnit:id,name', 'bookedByUser:id,first_name,last_name'])
            ->withCount('items')
            ->orderByDesc('consignments.created_at')
            ->paginate($perPage);

        $page->getCollection()->transform(fn (Consignment $c) => [
            'id' => $c->id,
            'consignment_number' => $c->consignment_number,
            'tracking_number' => $c->tracking_number,
            'created_at' => $c->created_at?->toIso8601String(),
            'sender_name' => $c->sender_name,
            'sender_phone' => $c->sender_phone,
            'receiver_name' => $c->receiver_name,
            'receiver_phone' => $c->receiver_phone,
            'route' => $c->route?->name,
            'departure_date' => $c->transportService?->departure_date,
            'departure_time' => $c->transportService?->departure_time ? substr($c->transportService->departure_time, 0, 5) : null,
            'unit' => $c->transportService?->transportUnit?->name,
            'status' => $c->status,
            'payment_status' => $c->payment_status,
            'payment_method' => $c->payment_method_label,
            'amount' => $c->billing_amount,
            'currency' => $c->billing_currency,
            'items_count' => $c->items_count,
            'weight_kg' => (float) $c->total_weight_kg,
            'booked_by' => $c->bookedByUser?->full_name,
        ]);

        // Counts per status for the tabs (every filter except status itself)
        $counts = $this->consignmentQuery($request, false)
            ->select('consignments.status')
            ->selectRaw('COUNT(*) AS n')
            ->groupBy('consignments.status')
            ->pluck('n', 'status');

        return response()->json(array_merge($page->toArray(), ['status_counts' => $counts]));
    }

    public function exportConsignments(Request $request)
    {
        $filename = 'Cargo_Bookings_' . now()->format('Ymd_His') . '.xlsx';

        return \App\Support\ExcelDownload::make($filename, fn ($writer) => $this->consignmentQuery($request)
            ->with(['route:id,name', 'transportService:id,departure_date,departure_time,transport_unit_id', 'transportService.transportUnit:id,name', 'bookedByUser:id,first_name,last_name', 'paymentChannel'])
            ->withCount('items')
            ->orderByDesc('consignments.created_at')
            ->chunk(500, function ($rows) use ($writer) {
                foreach ($rows as $c) {
                    $writer->addRow([
                        'Booking No.' => $c->consignment_number,
                        'Booked At' => $c->created_at?->format('Y-m-d H:i'),
                        'Departure' => trim(($c->transportService?->departure_date ?? '') . ' ' . substr($c->transportService?->departure_time ?? '', 0, 5)),
                        'Route' => $c->route?->name,
                        'Vessel' => $c->transportService?->transportUnit?->name,
                        'Sender' => $c->sender_name,
                        'Sender Phone' => $c->sender_phone,
                        'Receiver' => $c->receiver_name,
                        'Receiver Phone' => $c->receiver_phone,
                        'Items' => $c->items_count,
                        'Weight (kg)' => (float) $c->total_weight_kg,
                        'Status' => \Illuminate\Support\Str::headline($c->status),
                        'Payment' => \Illuminate\Support\Str::headline($c->payment_status),
                        'Channel' => $c->payment_method_label,
                        'Currency' => $c->billing_currency,
                        'Amount' => $c->billing_amount,
                        'Discount' => (float) $c->discount_amount,
                        'Booked By' => $c->bookedByUser?->full_name,
                    ]);
                }
            }));
    }

    private function consignmentQuery(Request $request, bool $withStatus = true)
    {
        $query = Consignment::query()->select('consignments.*');

        if ($withStatus && $request->filled('status')) {
            $query->where('consignments.status', $request->status);
        }
        if ($request->filled('payment_status')) {
            $query->where('consignments.payment_status', $request->payment_status);
        }
        if ($request->filled('route_id')) {
            $query->where('consignments.route_id', $request->route_id);
        }
        if ($request->filled('transport_service_id')) {
            $query->where('consignments.transport_service_id', $request->transport_service_id);
        }
        if ($request->filled('payment_method')) {
            $query->where('consignments.payment_method', $request->payment_method);
        }
        if ($request->filled('booked_by')) {
            $query->where('consignments.booked_by_user_id', $request->booked_by);
        }

        // Dates filter on booking date, or on the departure date
        if ($request->filled('date_from') || $request->filled('date_to')) {
            if ($request->date_field === 'departure') {
                $query->whereHas('transportService', function ($q) use ($request) {
                    $q->when($request->filled('date_from'), fn ($q) => $q->whereDate('departure_date', '>=', $request->date_from))
                      ->when($request->filled('date_to'), fn ($q) => $q->whereDate('departure_date', '<=', $request->date_to));
                });
            } else {
                $query->when($request->filled('date_from'), fn ($q) => $q->whereDate('consignments.created_at', '>=', $request->date_from))
                      ->when($request->filled('date_to'), fn ($q) => $q->whereDate('consignments.created_at', '<=', $request->date_to));
            }
        }

        if ($request->filled('search')) {
            $term = trim($request->search);
            $digits = \App\Support\Msisdn::searchDigits($term);
            $query->where(function ($q) use ($term, $digits) {
                foreach (['consignment_number', 'tracking_number', 'sender_name', 'receiver_name'] as $col) {
                    $q->orWhere("consignments.{$col}", 'ilike', '%' . $term . '%');
                }
                // Phone numbers in any format, whole or part ("+255 754", "0754 12", "754123456")
                if ($digits) {
                    $q->orWhereRaw("regexp_replace(coalesce(consignments.sender_phone, ''), '\\D', '', 'g') LIKE ?", ['%' . $digits . '%'])
                      ->orWhereRaw("regexp_replace(coalesce(consignments.receiver_phone, ''), '\\D', '', 'g') LIKE ?", ['%' . $digits . '%']);
                }
            });
        }

        return $query;
    }

    public function getCargoTypes()
    {
        // fares_count: routes/vessels with a fare in force now (0 = can't be booked anywhere)
        return response()->json(CargoType::withCount('currentFares')->orderBy('category')->orderBy('sort_order')->orderBy('name')->get());
    }

    /**
     * Cargo types change through dual control (rules cargo_type_create / _update / _delete).
     */
    public function storeCargoType(Request $request)
    {
        $validated = $this->validateCargoType($request);

        return $this->submitChange($request, 'cargo_type_create', 'cargo_type', 0, ['after' => $validated], 'Cargo type created.');
    }

    public function updateCargoType(Request $request, CargoType $cargoType)
    {
        $validated = $this->validateCargoType($request, $cargoType);
        $before = $cargoType->only(array_keys($validated));

        return $this->submitChange($request, 'cargo_type_update', 'cargo_type', $cargoType->id,
            ['before' => $before, 'after' => array_merge($before, $validated)], 'Cargo type updated.');
    }

    public function destroyCargoType(Request $request, CargoType $cargoType)
    {
        if ($cargoType->fares()->exists()) {
            return response()->json(['message' => 'This cargo type has fares, so it cannot be deleted. Set it inactive instead.'], 422);
        }

        return $this->submitChange($request, 'cargo_type_delete', 'cargo_type', $cargoType->id,
            ['before' => $cargoType->only(['name', 'code', 'category', 'status'])], 'Cargo type deleted.');
    }

    private function validateCargoType(Request $request, ?CargoType $existing = null): array
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => $existing ? 'prohibited' : 'required|string|max:20|unique:cargo_types,code',
            'category' => 'required|string|max:30',
            'measurement_unit' => 'required|in:kg,cbm,unit,lane_meter',
            'lane_meters' => 'required|numeric|min:0',
            'max_weight_kg' => 'required|numeric|min:0',
            'requires_hazmat_declaration' => 'boolean',
            'is_fragile' => 'boolean',
            'icon' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);
        if (!$existing) {
            $validated['code'] = strtoupper($validated['code']);
        }
        $validated['requires_hazmat_declaration'] = (bool) ($validated['requires_hazmat_declaration'] ?? false);
        $validated['is_fragile'] = (bool) ($validated['is_fragile'] ?? false);

        return $validated;
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

    // ── Loading manifest ──────────────────────────────────────

    public function manifestPage(Request $request)
    {
        $service = $request->filled('service') ? TransportService::find($request->service) : null;

        return view('admin.cargo.manifest', [
            'date' => $service?->departure_date ?? ($request->date ?: today()->toDateString()),
            'serviceId' => $service?->id,
        ]);
    }

    /**
     * Departures on a date, with how much is booked on each (for the manifest picker).
     */
    public function getManifestServices(Request $request)
    {
        $request->validate(['date' => 'required|date']);

        $services = TransportService::with(['route:id,name', 'transportUnit:id,name'])
            ->whereDate('departure_date', $request->date)
            ->orderBy('departure_time')
            ->get();

        $booked = Consignment::whereIn('transport_service_id', $services->pluck('id'))
            ->groupBy('transport_service_id')
            ->selectRaw("transport_service_id,
                         COUNT(*) FILTER (WHERE status IN ('" . implode("','", CargoManifestService::MANIFEST_STATUSES) . "')) AS on_manifest,
                         COUNT(*) FILTER (WHERE status = 'pending_payment') AS awaiting")
            ->get()->keyBy('transport_service_id');

        return response()->json($services->map(fn (TransportService $s) => [
            'id' => $s->id,
            'service_number' => $s->service_number,
            'route' => $s->route?->name,
            'unit' => $s->transportUnit?->name,
            'time' => $s->departure_time ? substr($s->departure_time, 0, 5) : null,
            'status' => $s->status,
            'consignments' => (int) ($booked[$s->id]->on_manifest ?? 0),
            'awaiting_payment' => (int) ($booked[$s->id]->awaiting ?? 0),
        ]));
    }

    public function getManifest($transportServiceId, CargoManifestService $manifestSvc)
    {
        try {
            return response()->json($manifestSvc->generateManifest($transportServiceId));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['message' => 'Departure not found.'], 404);
        }
    }

    public function exportManifest(Request $request, TransportService $transportService, CargoManifestService $manifestSvc)
    {
        $manifest = $manifestSvc->generateManifest($transportService->id);
        $name = 'Manifest_' . ($manifest['service_number'] ?: $transportService->id) . '_' . $manifest['departure_date'];

        if ($request->format === 'excel') {
            return \App\Support\ExcelDownload::make($name . '.xlsx', function ($writer) use ($manifest) {
                foreach ($manifest['consignments'] as $c) {
                    foreach ($c['items'] as $item) {
                        $writer->addRow([
                            'Booking No.' => $c['consignment_number'],
                            'Sender' => $c['sender'],
                            'Sender Phone' => $c['sender_phone'],
                            'Receiver' => $c['receiver'],
                            'Receiver Phone' => $c['receiver_phone'],
                            'Item No.' => $item['item_number'],
                            'Barcode' => $item['barcode'],
                            'Cargo Type' => $item['type'],
                            'Description' => $item['description'],
                            'Vehicle Reg.' => $item['vehicle_reg_number'],
                            'Qty' => $item['quantity'],
                            'Weight (kg)' => $item['weight'],
                            'Volume (cbm)' => $item['volume_cbm'],
                            'Fragile' => $item['is_fragile'] ? 'Yes' : '',
                            'Hazmat' => $item['is_hazmat'] ? 'Yes' : '',
                            'Status' => \Illuminate\Support\Str::headline($item['status']),
                        ]);
                    }
                }
            });
        }

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.reports.cargo.manifest-pdf', compact('manifest'))
            ->setPaper('a4', 'landscape')
            ->stream($name . '.pdf');
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
    // Same engine as the sales portal: CargoFareCalculator prices, CargoInventoryService
    // holds space, CargoBookingService books. The browser never sends a price.
    // ═══════════════════════════════════════════════════════════

    public function bookingTerminal(Request $request)
    {
        $cargoTypes = CargoType::where('status', 'active')
            ->orderBy('category')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'code', 'category', 'measurement_unit', 'lane_meters', 'max_weight_kg', 'requires_hazmat_declaration', 'is_fragile']);

        return view('admin.cargo.booking', [
            'paymentMethods' => PaymentMethod::activeOptions(),
            'cargoTypes' => $cargoTypes,
            'canOverrideFare' => $request->user()->hasPermission('cargo-override-fare'),
        ]);
    }

    /**
     * Bookable departures for a day (`date`) or the next few days (`from` + `days`), with free space.
     */
    public function getAvailableServicesForBooking(Request $request)
    {
        return response()->json($this->bookableDepartures($request));
    }

    /**
     * Live price for the cart (called as the clerk types).
     */
    public function quoteBooking(Request $request)
    {
        return response()->json($this->quoteCart($request));
    }

    public function acquireLockAdmin(Request $request)
    {
        $lock = $this->holdCart($request);

        return response()->json(['lock_id' => $lock->id, 'expires_at' => $lock->expires_at]);
    }

    public function releaseLockAdmin(Request $request)
    {
        $this->releaseHold($request);

        return response()->json(['message' => 'Lock released']);
    }

    public function confirmBookingAdmin(Request $request)
    {
        [$consignment, $payment] = $this->bookCart($request);

        return response()->json([
            'message' => $payment->status === 'paid' ? 'Booking confirmed and paid.' : 'Booking reserved — awaiting payment.',
            'consignment' => $consignment->fresh()->load('items.cargoType'),
            'payment' => $payment->toApi(),
        ], 201);
    }

    /**
     * Previous senders/receivers with this phone number, newest first (autofill at the counter).
     */
    public function lookupCustomer(Request $request)
    {
        return response()->json($this->customerSuggestions($request));
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
            'handedOverBy',
        ]);
        // Load / hand over / correct buttons for this user (port operations)
        $actions = app(\App\Services\Cargo\CargoOperationsService::class)->actions($consignment, auth()->user());

        return view('admin.cargo.bookings.show', compact('consignment', 'actions'));
    }

    public function cancelConsignment(Request $request, Consignment $consignment, CargoBookingService $bookingSvc)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        // Through dual control (cargo_booking_cancel); the handler releases space, cancels items and logs tracking
        return $this->submitChange($request, 'cargo_booking_cancel', 'consignment', $consignment->id, [
            'before' => ['status' => $consignment->status, 'payment_status' => $consignment->payment_status],
            'after' => ['status' => 'cancelled', 'reason' => $request->reason, 'booking' => $consignment->consignment_number],
        ], 'Booking has been cancelled.', route('admin.cargo.bookings.show', $consignment));
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
