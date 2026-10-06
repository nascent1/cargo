<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\PaymentGatewayLog;
use App\Models\Receipt;
use App\Models\AgentAccount;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Payment Service — orchestrates all payment flows for the booking platform.
 *
 * Handles cash recording, mobile money initiation (via GatewayFactory),
 * agent wallet deduction (via FloatManagementService), and refund processing.
 */
class PaymentService
{
    protected GatewayFactory $gatewayFactory;
    protected FloatManagementService $floatService;
    protected SystemTransactionService $txService;

    public function __construct(
        GatewayFactory $gatewayFactory,
        FloatManagementService $floatService,
        SystemTransactionService $txService
    ) {
        $this->gatewayFactory = $gatewayFactory;
        $this->floatService = $floatService;
        $this->txService = $txService;
    }

    /**
     * Process a payment for a booking.
     *
     * @param Booking $booking
     * @param string  $method   cash|mpesa|stripe|agent_float|mock
     * @param array   $metadata Additional gateway-specific data
     * @return Payment
     */
    public function processPayment(Booking $booking, string $method, array $metadata = []): Payment
    {
        $amount = (float) ($booking->net_amount > 0 ? $booking->net_amount : $booking->total_amount);
        $currency = $booking->currency ?? 'TZS';

        return DB::transaction(function () use ($booking, $method, $amount, $currency, $metadata) {

            // Agent float payments use the FloatManagementService directly
            if ($method === 'agent_float') {
                return $this->processAgentFloatPayment($booking, $amount, $currency, $metadata);
            }

            // Resolve the gateway and process charge
            $gateway = $this->gatewayFactory->make($method);
            $startTime = microtime(true);
            $result = $gateway->charge($amount, $currency, $metadata);
            $responseTimeMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log gateway interaction
            PaymentGatewayLog::create([
                'booking_id'            => $booking->id,
                'gateway'               => $method,
                'event_type'            => 'charge',
                'direction'             => 'outgoing',
                'http_method'           => 'POST',
                'request_payload'       => ['amount' => $amount, 'currency' => $currency, 'metadata' => $metadata],
                'response_payload'      => $result['raw'] ?? [],
                'transaction_reference' => $result['transaction_id'] ?? null,
                'amount'                => $amount,
                'currency'              => $currency,
                'is_success'            => $result['success'] ?? false,
                'error_message'         => $result['success'] ? null : ($result['message'] ?? 'Unknown error'),
                'response_time_ms'      => $responseTimeMs,
            ]);

            if (!($result['success'] ?? false)) {
                throw new Exception('Payment failed: ' . ($result['message'] ?? 'Gateway returned failure'));
            }

            // Create the payment record
            $payment = Payment::create([
                'booking_id'            => $booking->id,
                'payment_method_id'     => $this->resolvePaymentMethodId($method),
                'transaction_reference' => $result['transaction_id'] ?? null,
                'amount'                => $amount,
                'currency'              => $currency,
                'status'                => 'completed',
                'gateway_response'      => $result['raw'] ?? [],
                'paid_at'               => now(),
            ]);

            // Update booking status
            $booking->update([
                'status'          => 'confirmed',
                'payment_channel' => $method,
            ]);

            // Record in unified transactions ledger
            $this->txService->recordTransaction([
                'transaction_category_id' => \App\Models\TransactionCategory::getIdBySlug('ticket-sale', 2),
                'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                'payment_type_id'         => \App\Models\PaymentType::where('slug', 'credit')->value('id'),
                'amount'                  => $amount,
                'currency_code'           => $currency,
                'payment_method_id'       => $payment->payment_method_id,
                'payment_reference'       => $payment->transaction_reference,
                'booking_id'              => $booking->id,
                'user_id'                 => $booking->booked_by_user_id ?? $booking->user_id,
                'description'             => "Payment for booking #{$booking->booking_reference} via {$method}",
            ]);

            Log::info("Payment processed for booking #{$booking->booking_reference}: {$currency} {$amount} via {$method}");

            return $payment;
        });
    }

    /**
     * Process agent float payment (deducts from agent wallet).
     */
    protected function processAgentFloatPayment(Booking $booking, float $amount, string $currency, array $metadata): Payment
    {
        $user = $booking->bookedBy ?? $booking->user;
        if (!$user) {
            throw new Exception('Cannot determine agent for float payment.');
        }

        // Find agent account via the agent relationship
        $agent = $user->agent ?? \App\Models\Agent::where('user_id', $user->id)->first();
        if (!$agent || !$agent->account) {
            // Check if user is an associate agent
            $associate = \App\Models\AssociateAgent::where('user_id', $user->id)->first();
            if ($associate) {
                $agent = $associate->agent;
            }
        }

        if (!$agent || !$agent->account) {
            throw new Exception('No agent account found for float payment.');
        }

        // Deduct from agent float
        $this->floatService->deductForTicketSale($agent->account, $booking, $amount);

        // Create payment record
        $payment = Payment::create([
            'booking_id'            => $booking->id,
            'payment_method_id'     => $this->resolvePaymentMethodId('agent_float'),
            'transaction_reference' => 'FLOAT-' . now()->format('YmdHis') . '-' . rand(100, 999),
            'amount'                => $amount,
            'currency'              => $currency,
            'status'                => 'completed',
            'gateway_response'      => ['agent_id' => $agent->id, 'method' => 'float_deduction'],
            'paid_at'               => now(),
        ]);

        // Update booking
        $booking->update([
            'status'          => 'confirmed',
            'payment_channel' => 'agent_float',
        ]);

        // Calculate and credit commission if applicable
        if ($agent->getEffectiveCommissionMethod() === 'post_sale') {
            $commRate = $agent->getEffectiveCommissionRate();
            if ($commRate > 0) {
                $commAmount = round(($amount * $commRate) / 100, 4);
                $this->creditCommission($agent->account, $booking, $commAmount, $commRate);
            }
        }

        return $payment;
    }

    /**
     * Credit commission to agent account after a sale.
     */
    protected function creditCommission(AgentAccount $account, Booking $booking, float $amount, float $rate): void
    {
        DB::transaction(function () use ($account, $booking, $amount, $rate) {
            $lockedAccount = AgentAccount::where('id', $account->id)->lockForUpdate()->firstOrFail();
            $balanceBefore = (float) $lockedAccount->balance;
            $balanceAfter = round($balanceBefore + $amount, 4);

            $lockedAccount->update(['balance' => $balanceAfter]);

            $commCatId = \App\Models\TransactionCategory::getIdBySlug('commission', 5);
            $this->txService->recordTransaction([
                'transaction_category_id' => $commCatId,
                'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                'payment_type_id'         => \App\Models\PaymentType::where('slug', 'credit')->value('id'),
                'amount'                  => $amount,
                'currency_code'           => $lockedAccount->currency ?? 'TZS',
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'booking_id'              => $booking->id,
                'user_id'                 => $lockedAccount->agent->user_id,
                'description'             => "Commission ({$rate}%) on booking #{$booking->booking_reference}",
                'metadata'                => ['commission_rate' => $rate, 'method' => 'post_sale'],
            ]);
        });
    }

    /**
     * Process a refund for a payment.
     */
    public function processRefund(Payment $payment, float $amount, string $reason = ''): Payment
    {
        return DB::transaction(function () use ($payment, $amount, $reason) {
            $booking = $payment->booking;

            // If agent float payment, credit back to agent
            if ($payment->paymentMethod?->slug === 'agent_float' || str_starts_with($payment->transaction_reference ?? '', 'FLOAT-')) {
                $user = $booking->bookedBy ?? $booking->user;
                $agent = $user?->agent ?? \App\Models\Agent::where('user_id', $user?->id)->first();
                if ($agent?->account) {
                    $this->floatService->creditRefund($agent->account, $booking, $amount, $reason);
                }
            } else {
                // Gateway refund
                $gatewayName = $payment->gateway_response['gateway'] ?? 'cash';
                try {
                    $gateway = $this->gatewayFactory->make($gatewayName);
                    $gateway->refund($payment->transaction_reference, $amount, $reason);
                } catch (Exception $e) {
                    Log::warning("Gateway refund failed for payment #{$payment->id}: {$e->getMessage()}");
                }
            }

            $payment->update([
                'status' => $amount >= $payment->amount ? 'refunded' : 'partial_refund',
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Resolve payment method ID from a method name string.
     */
    protected function resolvePaymentMethodId(string $method): ?int
    {
        $slugMap = [
            'cash'        => 'cash',
            'mpesa'       => 'mobile-money',
            'stripe'      => 'card',
            'agent_float' => 'agent-float',
            'mock'        => 'cash',
            'selcom'      => 'mobile-money',
            'tigo'        => 'mobile-money',
        ];

        $slug = $slugMap[$method] ?? $method;
        return \App\Models\PaymentMethod::where('slug', $slug)->value('id');
    }
}
