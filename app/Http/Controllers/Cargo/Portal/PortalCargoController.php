<?php

namespace App\Http\Controllers\Cargo\Portal;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Cargo\CargoFareCalculator;
use App\Services\Cargo\CargoInventoryService;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoReceiptService;
use App\Services\Cargo\CargoTrackingService;
use App\Services\Cargo\CargoPaymentService;
use App\Exceptions\CargoPaymentException;
use App\Models\TransportService;
use App\Models\CargoType;
use App\Models\CargoLock;
use App\Models\Consignment;
use App\Models\PaymentMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PortalCargoController extends Controller
{
    /**
     * Status transitions a portal user may apply, keyed to the permission that allows each.
     */
    private const STATUS_PERMISSIONS = [
        'loaded'     => 'cargo-load',
        'in_transit' => 'cargo-load',
        'delivered'  => 'cargo-offload',
        'collected'  => 'cargo-collect',
    ];

    protected $fareCalc;
    protected $inventorySvc;
    protected $bookingSvc;
    protected $receiptSvc;
    protected $paymentSvc;

    public function __construct(
        CargoFareCalculator $fareCalc,
        CargoInventoryService $inventorySvc,
        CargoBookingService $bookingSvc,
        CargoReceiptService $receiptSvc,
        CargoPaymentService $paymentSvc
    ) {
        $this->fareCalc = $fareCalc;
        $this->inventorySvc = $inventorySvc;
        $this->bookingSvc = $bookingSvc;
        $this->receiptSvc = $receiptSvc;
        $this->paymentSvc = $paymentSvc;
    }

    public function getAvailableServices(Request $request)
    {
        $date = $request->input('date', now()->toDateString());
        
        $query = TransportService::with(['route', 'transportUnit', 'cargoInventory'])
            ->availableForBooking()
            ->whereDate('departure_date', $date);

        // Support route_id filtering for quick-book modal
        if ($request->filled('route_id')) {
            $query->where('route_id', $request->input('route_id'));
        }

        return response()->json($query->get());
    }

    public function getCargoTypes()
    {
        $types = CargoType::where('status', 'active')
            ->orderBy('sort_order')
            ->get();
        return response()->json($types);
    }

    public function calculateFare(Request $request)
    {
        $request->validate([
            'transport_service_id' => 'required|exists:transport_services,id',
            'currency' => 'required|in:TZS,USD',
            'items' => 'required|array',
            'items.*.cargo_type_id' => 'required|exists:cargo_types,id',
            'items.*.weight_kg' => 'nullable|numeric|min:0',
            'items.*.volume_cbm' => 'nullable|numeric|min:0',
            'items.*.is_fragile' => 'nullable|boolean',
        ]);

        try {
            $calculation = $this->fareCalc->calculateForItems(
                $request->items,
                $request->transport_service_id,
                $request->currency
            );
            return response()->json($calculation);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function acquireLock(Request $request)
    {
        $request->validate([
            'transport_service_id' => 'required|exists:transport_services,id',
            'items' => 'required|array',
        ]);

        try {
            $lock = $this->inventorySvc->acquireLock(
                $request->transport_service_id,
                $request->items,
                auth()->id(),
                auth()->user()->agent_id ?? null
            );
            return response()->json(['lock_id' => $lock->id, 'expires_at' => $lock->expires_at]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function confirmBooking(Request $request)
    {
        $request->validate([
            'lock_id' => 'required|exists:cargo_locks,id',
            'sender_name' => 'required|string',
            'sender_phone' => 'required|string',
            'receiver_name' => 'required|string',
            'receiver_phone' => 'required|string',
            'currency' => 'required|in:TZS,USD',
            'payment_method' => ['required', Rule::exists('payment_methods', 'slug')->where('is_active', true)],
            'payer_msisdn' => 'nullable|string|max:20',
            'items' => 'required|array',
        ]);

        try {
            // Reject an unusable channel / payer number before anything is booked
            $method = PaymentMethod::where('slug', $request->payment_method)->firstOrFail();
            $this->paymentSvc->assertCanStart($method, $request->payer_msisdn);

            $lock = CargoLock::findOrFail($request->lock_id);
            
            // Recalculate to ensure absolute accuracy at checkout
            $calculation = $this->fareCalc->calculateForItems(
                $request->items,
                $lock->transport_service_id,
                $request->currency
            );

            $consignmentData = array_merge($request->only([
                'sender_name', 'sender_phone', 'sender_email', 'sender_id_type', 'sender_id_number',
                'receiver_name', 'receiver_phone', 'receiver_email', 'special_instructions'
            ]), [
                'route_id' => $lock->transportService->route_id,
                'currency' => $request->currency,
                'payment_method' => $request->payment_method,
                'total_discount' => $calculation['total_discount'],
                'net_amount' => $calculation['net_amount'],
                'breakdown' => $calculation,
                'has_hazmat' => $calculation['has_hazmat'],
                'has_fragile' => $calculation['has_fragile'],
                'has_livestock' => $calculation['has_livestock'],
            ]);

            // Extend items with calculated values and user inputs
            $calculatedItems = [];
            foreach ($calculation['items'] as $index => $calcItem) {
                $calculatedItems[] = array_merge($calcItem, [
                    'description' => $request->items[$index]['description'] ?? null,
                    'declared_value' => $request->items[$index]['declared_value'] ?? 0,
                    'length_m' => $request->items[$index]['length_m'] ?? null,
                    'width_m' => $request->items[$index]['width_m'] ?? null,
                    'height_m' => $request->items[$index]['height_m'] ?? null,
                ]);
            }

            // Booking (space held as pending_payment) and its first payment attempt succeed or fail together.
            // Cash completes immediately; push / Lipa Namba complete via the payment panel.
            [$consignment, $payment] = DB::transaction(function () use ($lock, $consignmentData, $calculatedItems, $method, $request) {
                $consignment = $this->bookingSvc->confirmBooking($lock, $consignmentData, $calculatedItems, auth()->id());
                $payment = $this->paymentSvc->start($consignment, $method, $request->payer_msisdn, $request->user());

                return [$consignment, $payment];
            });

            return response()->json([
                'message' => $payment->status === 'paid' ? 'Booking confirmed and paid' : 'Booking reserved — awaiting payment',
                'consignment_number' => $consignment->consignment_number,
                'consignment_id' => $consignment->id,
                'payment' => $payment->toApi(),
            ]);

        } catch (CargoPaymentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function cancelBooking(Request $request, Consignment $consignment)
    {
        $this->authorizeConsignment($consignment);
        abort_unless($this->canCancel($consignment), 403, 'You do not have permission to cancel cargo bookings.');

        $request->validate(['reason' => 'required|string|max:500']);

        try {
            $this->bookingSvc->cancelBooking($consignment, auth()->id(), $request->reason);
            return response()->json(['message' => 'Consignment cancelled successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function updateStatus(Request $request, Consignment $consignment, CargoTrackingService $trackingSvc)
    {
        $this->authorizeConsignment($consignment);

        $request->validate(['status' => ['required', Rule::in(array_keys(self::STATUS_PERMISSIONS))]]);

        $newStatus = $request->status;
        abort_unless($request->user()->hasPermission(self::STATUS_PERMISSIONS[$newStatus]), 403, 'You do not have permission to apply this status.');

        if (in_array($consignment->status, ['cancelled', 'collected', 'pending_payment'], true)) {
            return response()->json(['error' => 'Cannot update the status of a ' . str_replace('_', ' ', $consignment->status) . ' booking.'], 422);
        }

        $oldStatus = $consignment->status;
        $trackingSvc->logEvent($consignment, $newStatus, "Status updated from {$oldStatus} to {$newStatus}", auth()->id());

        return response()->json(['message' => 'Status updated to ' . str_replace('_', ' ', $newStatus)]);
    }

    public function printReceipt(Request $request, Consignment $consignment)
    {
        $this->authorizeConsignment($consignment);
        abort_unless($request->user()->hasPermission('booking:print'), 403, 'You do not have permission to print receipts.');
        abort_if($consignment->isAwaitingPayment(), 409, 'Payment pending — the receipt is available once the booking is paid.');

        $consignment->load([
            'items.cargoType',
            'route.origin',
            'route.destination',
            'transportService.transportUnit',
            'bookedByUser',
            'receipts',
            'paymentChannel',
        ]);

        return view('cargo.receipt', compact('consignment'));
    }

    /**
     * cargo-cancel holders, or the booker abandoning their own unpaid booking.
     */
    private function canCancel(Consignment $consignment): bool
    {
        $user = auth()->user();

        return $user->hasPermission('cargo-cancel')
            || ($consignment->isAwaitingPayment() && $consignment->booked_by_user_id === $user->id);
    }

    /**
     * Agents and associates may only act on consignments they booked.
     */
    private function authorizeConsignment(Consignment $consignment): void
    {
        $user = auth()->user();
        if (in_array($user->domain, ['agent', 'associate_agent']) && $consignment->booked_by_user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    public function releaseLock(Request $request)
    {
        $request->validate(['lock_id' => 'required|exists:cargo_locks,id']);

        try {
            $lock = CargoLock::findOrFail($request->lock_id);
            $this->inventorySvc->releaseLock($lock);
            return response()->json(['message' => 'Lock released']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Dashboard summary for cargo portal — KPIs, active routes, recent consignments
     */
    public function dashboardSummary(Request $request)
    {
        $today = now()->toDateString();
        $userId = auth()->id();

        // KPIs
        $bookingsToday = Consignment::whereDate('created_at', $today)->count();
        $revenueToday = Consignment::whereDate('created_at', $today)->sum('total_amount_tzs') ?: 0;
        $activeVoyages = TransportService::availableForBooking()
            ->whereDate('departure_date', $today)
            ->count();
        $avgWeight = Consignment::whereDate('created_at', $today)->avg('total_weight_kg') ?: 0;

        // Active routes with today's voyage info
        $routes = \App\Models\Route::where('status', 'active')
            ->with(['origin', 'destination'])
            ->get()
            ->map(function ($route) use ($today) {
                $todayServices = TransportService::where('route_id', $route->id)
                    ->whereDate('departure_date', $today)
                    ->with('cargoInventory')
                    ->get();

                $voyagesCount = $todayServices->count();
                $nextDeparture = $todayServices
                    ->where('departure_time', '>', now()->format('H:i:s'))
                    ->sortBy('departure_time')
                    ->first();

                // Calculate average capacity usage across today's voyages
                $capacityPct = 0;
                if ($voyagesCount > 0) {
                    $totalUsed = $todayServices->sum(fn($s) => $s->cargoInventory?->used_weight_kg ?? 0);
                    $totalCap = $todayServices->sum(fn($s) => $s->cargoInventory?->total_weight_capacity_kg ?? 0);
                    $capacityPct = $totalCap > 0 ? round(($totalUsed / $totalCap) * 100) : 0;
                }

                return [
                    'id' => $route->id,
                    'name' => $route->name ?: ($route->origin?->name . ' → ' . $route->destination?->name),
                    'origin' => $route->origin?->name ?? 'Origin',
                    'destination' => $route->destination?->name ?? 'Destination',
                    'voyages_today' => $voyagesCount,
                    'next_departure' => $nextDeparture?->departure_time ? date('H:i', strtotime($nextDeparture->departure_time)) : null,
                    'capacity_pct' => $capacityPct,
                ];
            });

        // Recent consignments
                $query = Consignment::with(['transportService.route']);
        $user = auth()->user();
        if (in_array($user->domain, ['agent', 'associate_agent'])) {
             $query->where('booked_by_user_id', $user->id);
        }
        $recent = $query->latest()
            ->take(5)
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'booking_number' => $c->consignment_number,
                'date' => $c->created_at?->format('d M Y'),
                'sender' => $c->sender_name,
                'route' => $c->transportService?->route?->name ?? '–',
                'amount' => $c->total_amount,
                'status' => $c->status,
            ]);

        return response()->json([
            'kpi' => [
                'bookings_today' => $bookingsToday,
                'revenue_today' => $revenueToday,
                'active_voyages' => $activeVoyages,
                'avg_weight_kg' => round($avgWeight, 1),
            ],
            'routes' => $routes,
            'recent_consignments' => $recent,
        ]);
    }
    public function getConsignments(Request $request)
    {
        $query = Consignment::with(['transportService.route', 'transportService.transportUnit']);
        
        // Filter by user if not admin/manager
        $user = auth()->user();
        if (in_array($user->domain, ['agent', 'associate_agent'])) {
             $query->where('booked_by_user_id', $user->id);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('consignment_number', 'like', "%{$search}%")
                  ->orWhere('sender_name', 'like', "%{$search}%")
                  ->orWhere('receiver_name', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function showConsignment(Consignment $consignment)
    {
        $this->authorizeConsignment($consignment);

        $consignment->load([
            'route.origin',
            'route.destination',
            'transportService.transportUnit',
            'items.cargoType',
            'bookedByUser:id,first_name,last_name,email',
            'receipts.issuedBy:id,first_name,last_name,email',
            'paymentChannel',
            'payments' => fn ($q) => $q->orderByDesc('id'),
            // Consignment-level events only; item-level rows duplicate them
            'trackingEvents' => fn ($q) => $q->whereNull('cargo_item_id')->orderByDesc('created_at')->orderByDesc('id'),
        ]);

        $user = auth()->user();
        $allowedStatuses = in_array($consignment->status, ['cancelled', 'collected', 'pending_payment'], true)
            ? []
            : array_keys(array_filter(self::STATUS_PERMISSIONS, fn ($perm) => $user->hasPermission($perm)));

        return response()->json(array_merge($consignment->toArray(), [
            'billing_currency'     => $consignment->billing_currency,
            'billing_amount'       => $consignment->billing_amount,
            'payment_method_label' => $consignment->payment_method_label,
            'tracking_events'      => $consignment->trackingEvents->map(fn ($e) => [
                'id'          => $e->id,
                'status'      => $e->to_status ?? $e->event_type,
                'description' => $e->notes,
                'location'    => $e->location_name,
                'created_at'  => $e->created_at,
            ]),
            'payments'             => $consignment->payments->map->toApi(),
            'open_payment'         => $consignment->payments->first(fn ($p) => $p->isOpen())?->toApi(),
            'actions' => [
                'can_cancel'       => $consignment->isCancellable() && $this->canCancel($consignment),
                'can_print'        => $user->hasPermission('booking:print') && !$consignment->isAwaitingPayment(),
                'can_take_payment' => $consignment->isAwaitingPayment() && $user->hasPermission('cargo-book'),
                'allowed_statuses' => $allowedStatuses,
            ],
        ]));
    }
}
