<?php

namespace App\Services\Cargo;

use App\Exceptions\CargoPaymentException;
use App\Gateways\Payment\Push\AbstractPushGateway;
use App\Gateways\Payment\Push\PushGatewayManager;
use App\Gateways\Payment\Push\PushResult;
use App\Gateways\Payment\Push\SimulatorGateway;
use App\Jobs\InitiateUssdPush;
use App\Jobs\SimulateUssdCallback;
use App\Models\CargoPayment;
use App\Models\Consignment;
use App\Models\IntegrationConfig;
use App\Models\PaymentGatewayLog;
use App\Models\PaymentMethod;
use App\Models\PaymentType;
use App\Models\SystemConfig;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\ExchangeRateService;
use App\Services\SmsService;
use App\Services\SystemTransactionService;
use App\Support\Msisdn;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Collects payment for cargo bookings: cash, USSD push (per-MNO drivers) and Lipa Namba.
 *
 * A booking waits in `pending_payment` (space held) until one attempt is paid; unpaid
 * bookings are auto-cancelled by sweep() once their payment window closes.
 */
class CargoPaymentService
{
    public function __construct(
        protected PushGatewayManager $gateways,
        protected CargoBookingService $bookingSvc,
        protected CargoTrackingService $trackingSvc,
        protected CargoReceiptService $receiptSvc,
        protected SystemTransactionService $ledger,
    ) {
    }

    // ═══════════════════════════════════════════════════════════
    // Starting a payment
    // ═══════════════════════════════════════════════════════════

    /**
     * Validate channel + payer number up front (before a booking exists), returning the
     * normalised number.
     */
    public function assertCanStart(PaymentMethod $method, ?string $payerMsisdn): ?string
    {
        if (!$method->is_active || !$method->isCheckoutReady()) {
            throw new CargoPaymentException("{$method->name} is not available right now. Choose another payment method.");
        }

        $msisdn = Msisdn::normalize($payerMsisdn);
        if ($method->flow !== CargoPayment::FLOW_USSD) {
            return $msisdn;
        }

        if (!$msisdn) {
            throw new CargoPaymentException("Enter the payer's mobile number (e.g. 0754 123 456) to send the USSD push.");
        }

        // MNO-specific channels can only push to their own network (Selcom Pesa reaches any wallet)
        $network = Msisdn::network($msisdn);
        if (isset(Msisdn::NETWORK_PREFIXES[$method->driver]) && $network !== $method->driver) {
            throw new CargoPaymentException(sprintf(
                '%s is %s number — %s can only push to %s numbers.',
                Msisdn::local($msisdn),
                $network ? (preg_match('/^[AEIOU]/i', Msisdn::NETWORK_LABELS[$network]) ? 'an ' : 'a ') . Msisdn::NETWORK_LABELS[$network] : 'not a recognised mobile-money',
                $method->name,
                Msisdn::NETWORK_LABELS[$method->driver],
            ));
        }

        return $msisdn;
    }

    /**
     * Open a payment attempt on a booking awaiting payment. Any previous open attempt is
     * closed, so this also serves "resend push" and "switch channel".
     */
    public function start(Consignment $consignment, PaymentMethod $method, ?string $payerMsisdn, User $user): CargoPayment
    {
        if (!$consignment->isAwaitingPayment()) {
            throw new CargoPaymentException('This booking is not awaiting payment.');
        }

        $msisdn = $this->assertCanStart($method, $payerMsisdn);

        if ($method->flow === CargoPayment::FLOW_USSD) {
            $max = (int) SystemConfig::getValue('cargo_ussd_max_pushes', 3);
            $used = CargoPayment::where('consignment_id', $consignment->id)->where('flow', CargoPayment::FLOW_USSD)
                ->whereNull('metadata->not_live')->count();
            if ($used >= $max) {
                throw new CargoPaymentException("The limit of {$max} USSD push attempts for this booking has been reached. Take the payment by Lipa Namba or cash.");
            }
        }

        // A push channel without live credentials sends nothing: the booking stays pending payment
        $isPush = $method->flow === CargoPayment::FLOW_USSD;
        $configured = $isPush && $this->gateways->isConfigured($method->driver);
        $simulated = $isPush && !$configured && $this->gateways->simulatorAllowed();
        $notLive = $isPush && !$configured && !$simulated;

        $payment = DB::transaction(function () use ($consignment, $method, $msisdn, $user, $simulated, $notLive) {
            CargoPayment::where('consignment_id', $consignment->id)->open()->lockForUpdate()->get()
                ->each(fn (CargoPayment $p) => $p->update(['status' => 'cancelled', 'failure_reason' => 'Replaced by a new payment attempt.']));

            $payment = CargoPayment::create([
                'consignment_id'       => $consignment->id,
                'payment_method_id'    => $method->id,
                'flow'                 => $method->flow,
                'driver'               => $method->flow === CargoPayment::FLOW_USSD ? $method->driver : null,
                'amount'               => $consignment->billing_amount,
                'currency'             => $consignment->billing_currency,
                'payer_msisdn'         => $msisdn,
                'status'               => $method->flow === CargoPayment::FLOW_LIPA ? 'awaiting_customer' : 'initiated',
                'expires_at'           => match ($method->flow) {
                    CargoPayment::FLOW_USSD => now()->addMinutes((int) SystemConfig::getValue('cargo_ussd_timeout_minutes', 15)),
                    CargoPayment::FLOW_LIPA => now()->addMinutes((int) SystemConfig::getValue('cargo_lipa_timeout_minutes', 30)),
                    default                 => null,
                },
                'initiated_by_user_id' => $user->id,
                'metadata'             => array_filter(['simulated' => $simulated, 'not_live' => $notLive]),
            ]);

            $consignment->update(['payment_method' => $method->slug]);

            if ($method->flow === CargoPayment::FLOW_CASH) {
                $this->markPaid($payment, null, $user, 'cash');
            } elseif ($notLive) {
                $this->markFailed($payment, $this->notLiveReason($method->name));
            } elseif ($method->flow === CargoPayment::FLOW_USSD) {
                InitiateUssdPush::dispatch($payment->id)->afterCommit();
            }

            return $payment;
        });

        return $payment->fresh();
    }

    // ═══════════════════════════════════════════════════════════
    // USSD push lifecycle
    // ═══════════════════════════════════════════════════════════

    /**
     * Send the push. Claimed atomically, so the queue worker, sweep and poll fallbacks
     * can race without ever pushing the customer twice.
     */
    public function sendPush(CargoPayment $payment): void
    {
        $claimed = CargoPayment::whereKey($payment->id)->where('status', 'initiated')
            ->update(['status' => 'sending', 'updated_at' => now()]);
        if (!$claimed) {
            return;
        }

        $payment->refresh();

        // Credentials removed (or simulator switched off) since the attempt was opened
        if (!$payment->isSimulated() && !$this->gateways->isConfigured($payment->driver)) {
            $this->markFailed($payment, $this->notLiveReason($payment->paymentMethod?->name ?? 'This channel'));

            return;
        }

        try {
            $gateway = $this->gateways->forPayment($payment);
            $result = $gateway->initiate($payment);
        } catch (\Throwable $e) {
            Log::error("USSD push failed for {$payment->reference}: {$e->getMessage()}");
            $this->markFailed($payment, 'Could not reach ' . ($payment->paymentMethod?->name ?? 'the provider') . ': ' . $e->getMessage());

            return;
        }

        $this->applyResult($payment, $result, 'push');

        if ($gateway instanceof SimulatorGateway && $result->status === PushResult::PENDING) {
            SimulateUssdCallback::dispatch($payment->id)->delay(now()->addSeconds(SimulatorGateway::DELAY_SECONDS));
        }
    }

    /**
     * Ask the provider for the latest status of an in-flight push.
     */
    public function refreshStatus(CargoPayment $payment): void
    {
        if ($payment->flow !== CargoPayment::FLOW_USSD || !in_array($payment->status, ['sending', 'awaiting_customer'], true)) {
            return;
        }

        try {
            $gateway = $this->gateways->forPayment($payment);
            if (!$gateway->supportsStatusQuery()) {
                $payment->forceFill(['last_checked_at' => now()])->save();

                return;
            }
            $result = $gateway->queryStatus($payment);
        } catch (\Throwable $e) {
            Log::warning("Status check failed for {$payment->reference}: {$e->getMessage()}");
            $payment->forceFill(['last_checked_at' => now()])->save();

            return;
        }

        $this->applyResult($payment, $result, 'status_query');
    }

    /**
     * Called by the polling endpoint: self-heals a missing queue worker and catches
     * lost callbacks, throttled so providers aren't hammered.
     */
    public function refreshForPoll(CargoPayment $payment): void
    {
        if ($payment->flow !== CargoPayment::FLOW_USSD) {
            return;
        }

        if ($payment->status === 'initiated' && $payment->created_at->lt(now()->subSeconds(20))) {
            InitiateUssdPush::dispatchAfterResponse($payment->id);

            return;
        }

        $interval = $payment->isSimulated() ? 3 : 15;
        if ($payment->status === 'awaiting_customer' && (!$payment->last_checked_at || $payment->last_checked_at->lt(now()->subSeconds($interval)))) {
            $this->refreshStatus($payment);
        }
    }

    /**
     * Apply a provider outcome (push response, status query or callback) to an attempt.
     */
    public function applyResult(CargoPayment $payment, PushResult $result, string $source): void
    {
        if ($result->isPaid()) {
            $this->markPaid($payment, $result->providerReference, null, $source, $result->amount);

            return;
        }

        if ($result->isFailed()) {
            $this->markFailed($payment, $result->message ?? 'The payment was not completed.', $result->providerRequestId);

            return;
        }

        DB::transaction(function () use ($payment, $result) {
            $p = CargoPayment::whereKey($payment->id)->lockForUpdate()->first();
            if (!in_array($p->status, ['sending', 'awaiting_customer'], true)) {
                return;
            }

            $metadata = $p->metadata ?? [];
            $metadata['pushed_at'] ??= now()->toIso8601String();

            $p->fill([
                'status'              => 'awaiting_customer',
                'last_checked_at'     => now(),
                'provider_request_id' => $p->provider_request_id ?? $result->providerRequestId,
                'metadata'            => $metadata,
            ])->save();
        });

        $payment->refresh();
    }

    // ═══════════════════════════════════════════════════════════
    // Lipa Namba
    // ═══════════════════════════════════════════════════════════

    /**
     * Officer confirms a Lipa Namba payment using the transaction code from the business SMS.
     */
    public function confirmManual(CargoPayment $payment, string $reference, ?string $payerMsisdn, User $user): void
    {
        if ($payment->flow !== CargoPayment::FLOW_LIPA) {
            throw new CargoPaymentException('Only Lipa Namba payments are confirmed with a transaction reference.');
        }
        if (!$payment->isOpen()) {
            throw new CargoPaymentException('This payment is no longer open (' . str_replace('_', ' ', $payment->status) . ').');
        }

        $ref = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $reference));
        if (strlen($ref) < 6) {
            throw new CargoPaymentException('Enter the full transaction code from the payment SMS.');
        }

        $this->assertReferenceUnused($ref, $payment);

        try {
            DB::transaction(function () use ($payment, $ref, $payerMsisdn, $user) {
                $payment->update([
                    'manual_reference' => $ref,
                    'payer_msisdn'     => Msisdn::normalize($payerMsisdn) ?? $payment->payer_msisdn,
                ]);
                $this->markPaid($payment, null, $user, 'lipa_namba');
            });
        } catch (UniqueConstraintViolationException) {
            // Another officer used the same code a moment ago
            $this->assertReferenceUnused($ref, $payment);
            throw new CargoPaymentException("Reference {$ref} has already been used.");
        }
    }

    public function sendLipaInstructions(CargoPayment $payment, ?string $phone): array
    {
        if ($payment->flow !== CargoPayment::FLOW_LIPA || !$payment->isOpen()) {
            throw new CargoPaymentException('Instructions can only be sent for an open Lipa Namba payment.');
        }

        $msisdn = Msisdn::normalize($phone) ?? $payment->payer_msisdn ?? Msisdn::normalize($payment->consignment?->sender_phone);
        if (!$msisdn) {
            throw new CargoPaymentException('Enter a valid phone number to send the instructions to.');
        }

        $method = $payment->paymentMethod;
        $amount = $payment->currency . ' ' . number_format((float) $payment->amount);
        $ref = $payment->consignment?->consignment_number;
        $text = "Lipa {$amount} kwa Lipa Namba {$method->account_number}" . ($method->account_name ? " ({$method->account_name})" : '')
            . ". Kumbukumbu: {$ref}. / Pay {$amount} to Lipa Namba {$method->account_number}, reference {$ref}.";

        $gatewayConfigured = IntegrationConfig::where('channel', 'sms')->where('is_active', true)->exists();
        $sent = app(SmsService::class)->sendSms($msisdn, $text);

        return [
            'sent'      => $sent && $gatewayConfigured,
            'simulated' => !$gatewayConfigured,
            'to'        => Msisdn::mask($msisdn),
            'message'   => $text,
        ];
    }

    // ═══════════════════════════════════════════════════════════
    // Outcomes
    // ═══════════════════════════════════════════════════════════

    /**
     * Idempotent. Completes the booking: status booked, payment recorded, tracking event,
     * ledger entry, receipt, and (after commit) an SMS to the sender.
     */
    public function markPaid(CargoPayment $payment, ?string $providerReference, ?User $by, string $source, ?float $receivedAmount = null): void
    {
        DB::transaction(function () use ($payment, $providerReference, $by, $source, $receivedAmount) {
            $p = CargoPayment::whereKey($payment->id)->lockForUpdate()->first();
            if (in_array($p->status, ['paid', 'refund_due'], true)) {
                return;
            }

            if ($receivedAmount !== null && abs($receivedAmount - (float) $p->amount) > 0.5) {
                $p->fill([
                    'status'             => 'failed',
                    'provider_reference' => $providerReference ?? $p->provider_reference,
                    'failure_reason'     => sprintf('Amount mismatch: provider reported %s, expected %s. Refer to finance.', $receivedAmount, $p->amount),
                ])->save();
                Log::error("Cargo payment {$p->reference} amount mismatch ({$receivedAmount} vs {$p->amount})");

                return;
            }

            $consignment = Consignment::whereKey($p->consignment_id)->lockForUpdate()->first();
            $p->provider_reference = $providerReference ?? $p->provider_reference;
            $p->confirmed_at = now();
            $p->confirmed_by_user_id = $by?->id;

            // Money arrived for a booking that is already paid or was cancelled
            if (!$consignment->isAwaitingPayment()) {
                $p->status = 'refund_due';
                $p->failure_reason = $consignment->payment_status === 'paid'
                    ? 'Duplicate payment: the booking was already paid. Refund required.'
                    : 'Paid after the booking was cancelled. Refund required.';
                $p->save();

                TrackingEvent::create([
                    'consignment_id'  => $consignment->id,
                    'event_type'      => 'payment_refund_due',
                    'to_status'       => $consignment->status,
                    'notes'           => $p->failure_reason . ' Ref ' . ($p->provider_reference ?? $p->reference),
                    'performed_by_id' => $by?->id,
                    'created_at'      => now(),
                ]);
                Log::warning("Cargo payment {$p->reference} received for {$consignment->consignment_number} in status {$consignment->status}: refund due");

                return;
            }

            $p->status = 'paid';
            $p->failure_reason = null;
            $p->save();

            CargoPayment::where('consignment_id', $consignment->id)->open()->where('id', '!=', $p->id)
                ->update(['status' => 'cancelled', 'failure_reason' => 'Booking paid via another attempt.', 'updated_at' => now()]);

            $method = $p->paymentMethod;
            $reference = $p->manual_reference ?? $p->provider_reference ?? $p->reference;
            $actorId = $by?->id ?? $p->initiated_by_user_id;

            $consignment->update([
                'payment_status'    => 'paid',
                'payment_method'    => $method->slug,
                'payment_reference' => $reference,
                'paid_at'           => now(),
            ]);

            $this->trackingSvc->logEvent(
                $consignment,
                'booked',
                "Payment received via {$method->name}" . ($p->flow === CargoPayment::FLOW_CASH ? '' : " (ref {$reference})"),
                $actorId,
            );

            if ((float) $p->amount > 0) {
                $this->ledger->recordTransaction([
                    'category'          => 'cargo-booking',
                    'status'            => 'completed',
                    'payment_type_id'   => PaymentType::where('slug', $p->flow === CargoPayment::FLOW_CASH ? 'cash' : 'payment')->value('id'),
                    'amount'            => (float) $p->amount,
                    'currency_code'     => $p->currency,
                    'exchange_rate'     => ExchangeRateService::getCurrentRate($p->currency, 'TZS'),
                    'payment_method_id' => $p->payment_method_id,
                    'payment_reference' => $reference,
                    'consignment_id'    => $consignment->id,
                    'user_id'           => $p->initiated_by_user_id,
                    'description'       => "Cargo booking {$consignment->consignment_number} via {$method->name}",
                    'metadata'          => ['cargo_payment_id' => $p->id, 'flow' => $p->flow, 'source' => $source],
                ]);
            }

            $this->receiptSvc->generateReceipt($consignment->fresh(['items.cargoType']), $actorId);

            // Booking details to the sender and the receiver, once the payment is committed (SMS policy)
            DB::afterCommit(fn () => app(\App\Services\SmsNotifier::class)->bookingConfirmed($consignment->fresh()));
        });

        $payment->refresh();
    }

    public function markFailed(CargoPayment $payment, string $reason, ?string $providerRequestId = null): void
    {
        DB::transaction(function () use ($payment, $reason, $providerRequestId) {
            $p = CargoPayment::whereKey($payment->id)->lockForUpdate()->first();
            if (!$p->isOpen()) {
                return;
            }

            $p->fill([
                'status'              => 'failed',
                'failure_reason'      => $reason,
                'last_checked_at'     => now(),
                'provider_request_id' => $p->provider_request_id ?? $providerRequestId,
            ])->save();
        });

        $payment->refresh();
    }

    // ═══════════════════════════════════════════════════════════
    // Provider callbacks
    // ═══════════════════════════════════════════════════════════

    /**
     * Never trust a callback body alone: when the provider supports a status query the
     * outcome is re-confirmed with it; otherwise the callback must authenticate.
     */
    public function handleCallback(string $driver, Request $request): Response
    {
        try {
            $gateway = $this->gateways->make($driver);
        } catch (\Throwable) {
            $this->logIncoming($driver, $request, null, false, 'Channel not configured');

            return response()->json(['status' => 'rejected', 'message' => 'Unknown or unconfigured payment channel.'], 404);
        }

        $verified = $gateway->verifyCallback($request);
        $parsed = $gateway->parseCallback($request);
        $payment = $parsed ? $this->findPaymentForCallback($driver, $parsed) : null;
        $this->logIncoming($driver, $request, $payment, $verified, $payment ? null : 'No matching payment');

        if (!$payment) {
            return $gateway->callbackAck(false);
        }

        if ($gateway->supportsStatusQuery()) {
            try {
                $confirmed = $gateway->queryStatus($payment);
                $result = $confirmed->status === PushResult::PENDING && $verified ? $parsed : $confirmed;
            } catch (\Throwable $e) {
                Log::warning("Callback re-check failed for {$payment->reference}: {$e->getMessage()}");
                if (!$verified) {
                    return $gateway->callbackAck(false);
                }
                $result = $parsed;
            }
        } elseif ($verified) {
            $result = $parsed;
        } else {
            Log::warning("Rejected unauthenticated {$driver} callback for {$payment->reference}");

            return $gateway->callbackAck(false);
        }

        $this->applyResult($payment, $result, 'callback');

        return $gateway->callbackAck(true);
    }

    private function findPaymentForCallback(string $driver, PushResult $parsed): ?CargoPayment
    {
        if (!$parsed->ourReference && !$parsed->providerRequestId) {
            return null;
        }

        return CargoPayment::where('driver', $driver)
            ->where(function ($q) use ($parsed) {
                if ($parsed->ourReference) {
                    $q->orWhere('reference', $parsed->ourReference);
                }
                if ($parsed->providerRequestId) {
                    $q->orWhere('provider_request_id', $parsed->providerRequestId);
                }
            })
            ->latest('id')
            ->first();
    }

    private function logIncoming(string $driver, Request $request, ?CargoPayment $payment, bool $verified, ?string $error): void
    {
        try {
            PaymentGatewayLog::create([
                'consignment_id'        => $payment?->consignment_id,
                'cargo_payment_id'      => $payment?->id,
                'gateway'               => $driver,
                'event_type'            => 'callback',
                'direction'             => 'incoming',
                'http_method'           => $request->method(),
                'url'                   => $request->path(),
                'request_headers'       => AbstractPushGateway::maskData(collect($request->headers->all())->map(fn ($v) => implode(', ', $v))->all()),
                'request_payload'       => AbstractPushGateway::maskData($request->all()),
                'transaction_reference' => $payment?->reference,
                'amount'                => $payment?->amount,
                'currency'              => $payment?->currency,
                'is_success'            => $payment !== null,
                'error_message'         => $error ?? ($verified ? null : 'Callback not authenticated; outcome re-checked with provider'),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to log payment callback: ' . $e->getMessage());
        }
    }

    // ═══════════════════════════════════════════════════════════
    // Housekeeping (scheduled every minute)
    // ═══════════════════════════════════════════════════════════

    public function sweep(): array
    {
        $stats = ['pushed' => 0, 'refreshed' => 0, 'expired' => 0, 'cancelled' => 0];

        // 1. Pushes the queue never picked up
        CargoPayment::where('status', 'initiated')->where('flow', CargoPayment::FLOW_USSD)
            ->where('created_at', '<', now()->subSeconds(45))->get()
            ->each(function (CargoPayment $p) use (&$stats) {
                $this->sendPush($p);
                $stats['pushed']++;
            });

        // 2. Lost callbacks: re-check pushes that have gone quiet
        CargoPayment::whereIn('status', ['sending', 'awaiting_customer'])->where('flow', CargoPayment::FLOW_USSD)
            ->where('updated_at', '<', now()->subSeconds(60))
            ->where(fn ($q) => $q->whereNull('last_checked_at')->orWhere('last_checked_at', '<', now()->subSeconds(60)))
            ->get()
            ->each(function (CargoPayment $p) use (&$stats) {
                $this->refreshStatus($p);
                $stats['refreshed']++;
            });

        // 3. Expire attempts past their deadline — one last status check first, so a late payment isn't lost
        CargoPayment::open()->whereNotNull('expires_at')->where('expires_at', '<', now())->get()
            ->each(function (CargoPayment $p) use (&$stats) {
                $this->refreshStatus($p);
                $p->refresh();
                if ($p->isOpen()) {
                    $p->update(['status' => 'expired', 'failure_reason' => 'No payment received before the deadline.']);
                    $stats['expired']++;
                }
            });

        // 4. Release bookings whose payment window has closed
        Consignment::where('status', 'pending_payment')
            ->whereDoesntHave('payments', fn ($q) => $q->whereIn('status', CargoPayment::OPEN_STATUSES))
            ->get()
            ->each(function (Consignment $c) use (&$stats) {
                $deadline = CargoPayment::where('consignment_id', $c->id)->max('expires_at');
                $deadline = $deadline
                    ? Carbon::parse($deadline)
                    : $c->created_at->copy()->addMinutes((int) SystemConfig::getValue('cargo_ussd_timeout_minutes', 15));

                if ($deadline->isFuture()) {
                    return;
                }

                try {
                    $this->bookingSvc->cancelBooking($c, $c->booked_by_user_id, 'Payment not received in time. Booking auto-cancelled and space released.');
                    $stats['cancelled']++;
                } catch (\Throwable $e) {
                    Log::error("Auto-cancel of unpaid booking {$c->consignment_number} failed: {$e->getMessage()}");
                }
            });

        return $stats;
    }

    private function notLiveReason(string $channel): string
    {
        return "No push was sent: {$channel} isn't live yet (its credentials aren't set up in Settings › Payment Channels). "
            . 'The space stays reserved — take the payment through another channel.';
    }

    private function assertReferenceUnused(string $ref, CargoPayment $payment): void
    {
        $duplicate = CargoPayment::with('consignment')
            ->where('id', '!=', $payment->id)
            ->where(fn ($q) => $q->where('manual_reference', $ref)->orWhere('provider_reference', $ref))
            ->first();

        if ($duplicate) {
            throw new CargoPaymentException("Reference {$ref} was already used for booking {$duplicate->consignment?->consignment_number}. Check the SMS again.");
        }
    }
}
