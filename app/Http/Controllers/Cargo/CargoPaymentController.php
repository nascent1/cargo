<?php

namespace App\Http\Controllers\Cargo;

use App\Exceptions\CargoPaymentException;
use App\Http\Controllers\Controller;
use App\Models\CargoPayment;
use App\Models\Consignment;
use App\Models\PaymentMethod;
use App\Services\Cargo\CargoPaymentService;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Payment actions shared by the sales portal and the admin portal
 * (mounted under /portal/api/cargo and /admin/cargo/api).
 */
class CargoPaymentController extends Controller
{
    public function __construct(protected CargoPaymentService $payments)
    {
    }

    public function methods()
    {
        return response()->json(PaymentMethod::activeOptions());
    }

    /**
     * Polled by the payment panel; also self-heals a stalled push and lost callbacks.
     */
    public function show(CargoPayment $cargoPayment)
    {
        $this->authorizeConsignment($cargoPayment->consignment);
        $this->payments->refreshForPoll($cargoPayment);

        $payment = $cargoPayment->fresh();

        return response()->json($payment->toApi() + [
            // Push not picked up by a queue worker yet (it is then sent inline as a fallback)
            'worker_delayed' => $payment->status === 'initiated' && $payment->created_at->lt(now()->subSeconds(20)),
        ]);
    }

    /**
     * Start a new attempt on a booking awaiting payment (switch channel, retry after failure).
     */
    public function start(Request $request, Consignment $consignment)
    {
        $this->authorizeConsignment($consignment);
        $request->validate([
            'payment_method' => ['required', Rule::exists('payment_methods', 'slug')->where('is_active', true)],
            'payer_msisdn'   => 'nullable|string|max:20',
        ]);

        $method = PaymentMethod::where('slug', $request->payment_method)->firstOrFail();

        return $this->attempt(fn () => $this->payments->start($consignment, $method, $request->payer_msisdn, $request->user())->toApi());
    }

    public function resend(Request $request, CargoPayment $cargoPayment)
    {
        $this->authorizeConsignment($cargoPayment->consignment);
        $request->validate(['payer_msisdn' => 'nullable|string|max:20']);

        if ($cargoPayment->flow !== CargoPayment::FLOW_USSD) {
            return response()->json(['error' => 'Only USSD push payments can be resent.'], 422);
        }

        return $this->attempt(fn () => $this->payments->start(
            $cargoPayment->consignment,
            $cargoPayment->paymentMethod,
            $request->payer_msisdn ?: $cargoPayment->payer_msisdn,
            $request->user(),
        )->toApi());
    }

    /**
     * Lipa Namba: officer records the transaction code from the business SMS.
     */
    public function confirmManual(Request $request, CargoPayment $cargoPayment)
    {
        $this->authorizeConsignment($cargoPayment->consignment);
        $request->validate([
            'reference'    => 'required|string|max:100',
            'payer_msisdn' => 'nullable|string|max:20',
        ]);

        // Through dual control (cargo_payment_manual_confirm): applied straight away when the rule is off,
        // otherwise a checker confirms it and the payment stays open meanwhile (202 + message).
        $dualControl = app(DualControlService::class);
        try {
            $dcRequest = $dualControl->submit('cargo_payment_manual_confirm', 'cargo_payment', $cargoPayment->id, [
                'before' => ['status' => $cargoPayment->status, 'amount' => (float) $cargoPayment->amount],
                'after' => [
                    'reference' => $request->reference,
                    'payer_msisdn' => $request->payer_msisdn,
                    'booking' => $cargoPayment->consignment?->consignment_number,
                ],
            ]);
        } catch (CargoPaymentException|DualControlException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }

        if ($dcRequest->status !== 'approved') {
            return response()->json(['pending' => true, 'request_id' => $dcRequest->id, 'message' => $dualControl->outcomeMessage($dcRequest, '')], 202);
        }

        return response()->json($cargoPayment->fresh()->toApi());
    }

    public function smsInstructions(Request $request, CargoPayment $cargoPayment)
    {
        $this->authorizeConsignment($cargoPayment->consignment);
        $request->validate(['phone' => 'nullable|string|max:20']);

        return $this->attempt(fn () => $this->payments->sendLipaInstructions($cargoPayment, $request->phone));
    }

    private function attempt(callable $action)
    {
        try {
            return response()->json($action());
        } catch (CargoPaymentException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Taking payments needs cargo-book; agents and associates only act on their own bookings.
     */
    private function authorizeConsignment(Consignment $consignment): void
    {
        $user = auth()->user();
        abort_unless($user->hasPermission('cargo-book'), 403, 'You do not have permission to take cargo payments.');

        if (in_array($user->domain, ['agent', 'associate_agent']) && $consignment->booked_by_user_id !== $user->id) {
            abort(403, 'Unauthorized');
        }
    }
}
