<?php

namespace App\Http\Controllers\Cargo\Portal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Cargo\Concerns\BuildsCargoCart;
use Illuminate\Http\Request;
use App\Services\Cargo\CargoBookingService;
use App\Services\Cargo\CargoDashboardService;
use App\Services\Cargo\CargoHandoverService;
use App\Services\Cargo\CargoOperationsService;
use App\Models\CargoType;
use App\Models\Consignment;
use Illuminate\Validation\Rule;

class PortalCargoController extends Controller
{
    use BuildsCargoCart;

    public function __construct(protected CargoBookingService $bookingSvc)
    {
    }

    /**
     * Bookable departures for a day (`date`) or the next few days (`from` + `days`), with free space.
     */
    public function getAvailableServices(Request $request)
    {
        return response()->json($this->bookableDepartures($request));
    }

    public function getCargoTypes()
    {
        $types = CargoType::where('status', 'active')
            ->orderBy('category')->orderBy('sort_order')->orderBy('name')
            ->get(['id', 'name', 'code', 'category', 'measurement_unit', 'lane_meters', 'max_weight_kg', 'requires_hazmat_declaration', 'is_fragile', 'icon']);

        return response()->json($types);
    }

    /**
     * Live price for the cart (called as the clerk types).
     */
    public function calculateFare(Request $request)
    {
        return response()->json($this->quoteCart($request));
    }

    public function acquireLock(Request $request)
    {
        $lock = $this->holdCart($request);

        return response()->json(['lock_id' => $lock->id, 'expires_at' => $lock->expires_at]);
    }

    public function confirmBooking(Request $request)
    {
        [$consignment, $payment] = $this->bookCart($request);

        return response()->json([
            'message' => $payment->status === 'paid' ? 'Booking confirmed and paid.' : 'Booking reserved — awaiting payment.',
            'consignment_number' => $consignment->consignment_number,
            'consignment_id' => $consignment->id,
            'payment' => $payment->toApi(),
        ]);
    }

    /**
     * Previous senders/receivers with this phone number. Agents only see customers from their own bookings.
     */
    public function lookupCustomer(Request $request)
    {
        $user = $request->user();

        return response()->json($this->customerSuggestions($request, $this->seesOnlyOwn($user) ? $user->id : null));
    }

    public function cancelBooking(Request $request, Consignment $consignment)
    {
        $this->authorizeConsignment($consignment);
        abort_unless($this->canCancel($consignment), 403, 'You do not have permission to cancel cargo bookings.');

        $request->validate(['reason' => 'required|string|max:500']);

        // Through dual control (cargo_booking_cancel); waits for a checker when that rule is on
        $dualControl = app(\App\Services\DualControlService::class);
        try {
            $dcRequest = $dualControl->submit('cargo_booking_cancel', 'consignment', $consignment->id, [
                'before' => ['status' => $consignment->status, 'payment_status' => $consignment->payment_status],
                'after' => ['status' => 'cancelled', 'reason' => $request->reason, 'booking' => $consignment->consignment_number],
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($dcRequest->status !== 'approved') {
            return response()->json(['pending' => true, 'request_id' => $dcRequest->id, 'message' => $dualControl->outcomeMessage($dcRequest, '')], 202);
        }

        return response()->json(['message' => 'Consignment cancelled successfully']);
    }

    /**
     * Mark loaded (before the vessel leaves). In transit and arrived come from the journey;
     * collection is the hand-over below.
     */
    public function updateStatus(Request $request, Consignment $consignment, CargoOperationsService $operations)
    {
        $this->authorizeConsignment($consignment);
        $request->validate(['status' => ['required', Rule::in(['loaded'])]]);
        abort_unless($request->user()->hasPermission('cargo-load'), 403, 'You do not have permission to load cargo.');

        try {
            $operations->load($consignment, $request->user()->id, 'sales portal');
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json(['message' => "{$consignment->consignment_number} marked loaded."]);
    }

    public function handoverCode(Request $request, Consignment $consignment, CargoHandoverService $handover)
    {
        $this->authorizeConsignment($consignment);
        $data = $request->validate(['phone' => 'required|string|max:20']);

        try {
            return response()->json($handover->sendCode($consignment, $data['phone']));
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function handover(Request $request, Consignment $consignment, CargoHandoverService $handover)
    {
        $this->authorizeConsignment($consignment);
        $data = $request->validate(\App\Http\Controllers\Cargo\Admin\CargoOperationsController::handoverRules());

        try {
            $result = $handover->handOver($consignment, $data, $request->user(), $request->ip());
        } catch (\DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        return response()->json($result + ['message' => "{$consignment->consignment_number} handed over to {$consignment->fresh()->collected_by_name}."]);
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
        if ($this->seesOnlyOwn($user) && $consignment->booked_by_user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }

    /**
     * Agents and associates work only with their own bookings and customers.
     */
    private function seesOnlyOwn($user): bool
    {
        return in_array($user->domain, ['agent', 'associate_agent'], true);
    }

    public function releaseLock(Request $request)
    {
        $this->releaseHold($request);

        return response()->json(['message' => 'Lock released']);
    }

    /**
     * The signed-in clerk's own day: their bookings, money they collected, what waits on them.
     */
    public function dashboardSummary(Request $request, CargoDashboardService $dashboard)
    {
        return response()->json($dashboard->counter($request->user()));
    }

    public function getConsignments(Request $request)
    {
        $query = Consignment::with(['route:id,name', 'transportService.route', 'transportService.transportUnit']);

        // Agents only ever see their own bookings; anyone can narrow the list to theirs ("mine")
        if ($this->seesOnlyOwn(auth()->user()) || $request->boolean('mine')) {
            $query->where('booked_by_user_id', auth()->id());
        }

        if ($search = trim((string) $request->input('search'))) {
            $digits = \App\Support\Msisdn::searchDigits($search);
            $query->where(function ($q) use ($search, $digits) {
                $q->where('consignment_number', 'ilike', "%{$search}%")
                  ->orWhere('tracking_number', 'ilike', "%{$search}%")
                  ->orWhere('sender_name', 'ilike', "%{$search}%")
                  ->orWhere('receiver_name', 'ilike', "%{$search}%");
                // Phone numbers in any format, whole or part ("+255 754", "0754 12", "754123456")
                if ($digits) {
                    $q->orWhereRaw("regexp_replace(coalesce(sender_phone, ''), '\\D', '', 'g') LIKE ?", ['%' . $digits . '%'])
                      ->orWhereRaw("regexp_replace(coalesce(receiver_phone, ''), '\\D', '', 'g') LIKE ?", ['%' . $digits . '%']);
                }
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
        $operations = app(CargoOperationsService::class)->actions($consignment, $user);

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
                'allowed_statuses' => $operations['load'] ? ['loaded'] : [],
                'can_load'         => $operations['load'],
                'can_handover'     => $operations['handover'],
            ],
        ]));
    }
}
