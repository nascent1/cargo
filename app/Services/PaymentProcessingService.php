<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\User;
use App\Models\SystemTransaction;
use App\Models\PaymentType;
use App\Models\PaymentMethod;
use App\Models\TransactionCategory;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * @deprecated Use {@see PaymentService} for all new payment operations.
 *
 * This service handles legacy payment flows (processStandardPayment, deductAgentFloat).
 * New code should use PaymentService which consolidates all payment orchestration
 * through GatewayFactory, FloatManagementService, and SystemTransactionService.
 *
 * Retained for backward compatibility — will be removed in a future release.
 */
class PaymentProcessingService
{
    /**
     * Process standard payment (cash, mobile, card) for a booking.
     */
    public function processStandardPayment(Booking $booking, array $paymentData, ?User $actor = null, bool $isOnlinePayment = false): void
    {
        $pmId = PaymentMethod::where('slug', 'cash')->value('id') ?? 1;
        if (!empty($paymentData['payment_channel'])) {
            $pmId = PaymentMethod::where('name', 'like', '%' . $paymentData['payment_channel'] . '%')->value('id') ?? $pmId;
        }

        $cashPaymentType = PaymentType::where('slug', 'cash')->first();
        $exchangeRate = ExchangeRateService::getCurrentRate('USD', 'TZS');
        
        $tzsToPay = $booking->total_amount_tzs > 0 ? $booking->total_amount_tzs : ($booking->currency === 'TZS' ? $booking->net_amount : 0);
        $usdToPay = $booking->total_amount_usd > 0 ? $booking->total_amount_usd : ($booking->currency === 'USD' ? $booking->net_amount : 0);

        if ($tzsToPay == 0 && $usdToPay == 0) {
            $tzsToPay = $booking->net_amount ?? $booking->total_amount;
        }
        
        $actorName = $actor?->full_name ?? 'System';
        $paymentReference = $paymentData['transaction_reference'] ?? $booking->booking_reference;
        $userId = $actor?->id ?? $booking->booked_by_user_id ?? $booking->user_id;

        DB::transaction(function () use ($tzsToPay, $usdToPay, $isOnlinePayment, $cashPaymentType, $exchangeRate, $pmId, $paymentReference, $booking, $userId, $actorName) {
            $catId = \App\Models\TransactionCategory::getIdBySlug('ticket-sale') ?? 2;
            $statusIdPending = \App\Models\TransactionStatus::getIdBySlug('pending') ?? 1;
            $statusIdCompleted = \App\Models\TransactionStatus::getIdBySlug('completed') ?? 4;
            $txStatusId = $isOnlinePayment ? $statusIdPending : $statusIdCompleted;

            if ($tzsToPay == 0 && $usdToPay == 0) {
                // Complimentary ticket / 100% discount
                SystemTransaction::create([
                    'transaction_number' => 'TX-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'transaction_category_id' => $catId,
                    'transaction_status_id' => $statusIdCompleted, // Always completed for free
                    'payment_type_id' => $cashPaymentType?->id,
                    'amount' => 0.00,
                    'currency_code' => 'TZS',
                    'exchange_rate' => 1.000000,
                    'amount_tzs' => 0.00,
                    'payment_method_id' => $pmId,
                    'payment_reference' => $paymentReference,
                    'booking_id' => $booking->id,
                    'user_id' => $userId,
                    'description' => "Complimentary/Free Booking #{$booking->booking_reference} (By: {$actorName})",
                ]);
            } else {
                if ($tzsToPay > 0) {
                    SystemTransaction::create([
                        'transaction_number' => 'TX-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'transaction_category_id' => $catId,
                        'transaction_status_id' => $txStatusId,
                        'payment_type_id' => $cashPaymentType?->id,
                        'amount' => $tzsToPay,
                        'currency_code' => 'TZS',
                        'exchange_rate' => 1.000000,
                        'amount_tzs' => $tzsToPay,
                        'payment_method_id' => $pmId,
                        'payment_reference' => $paymentReference,
                        'booking_id' => $booking->id,
                        'user_id' => $userId,
                        'description' => "Payment (Local) for confirmed booking #{$booking->booking_reference} (By: {$actorName})",
                    ]);
                }

                if ($usdToPay > 0) {
                    SystemTransaction::create([
                        'transaction_number' => 'TX-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'transaction_category_id' => $catId,
                        'transaction_status_id' => $txStatusId,
                        'payment_type_id' => $cashPaymentType?->id,
                        'amount' => $usdToPay,
                        'currency_code' => 'USD',
                        'exchange_rate' => $exchangeRate,
                        'amount_tzs' => round($usdToPay * $exchangeRate, 2),
                        'payment_method_id' => $pmId,
                        'payment_reference' => $paymentReference,
                        'booking_id' => $booking->id,
                        'user_id' => $userId,
                        'description' => "Payment (Foreigner) for confirmed booking #{$booking->booking_reference} (By: {$actorName})",
                    ]);
                }
            }
        });
    }

    /**
     * Process wallet deduction for Agent/Associate bookings.
     */
    public function processAgentWalletDeduction(Booking $booking, User $bookingUser, ?User $actor = null): void
    {
        if (!in_array($bookingUser->domain, ['agent', 'associate', 'associate_agent'])) {
            return; // Not an agent booking
        }

        $bookingRate = ($booking->exchange_rate && $booking->exchange_rate != 1.000000) ? $booking->exchange_rate : ExchangeRateService::getCurrentRate('USD', 'TZS');
        $netAmt = (!is_null($booking->net_amount) ? $booking->net_amount : $booking->total_amount);
        // When exchange rate conversion was disabled or not applied, net_amount only has TZS portion.
        // For agent wallet (always TZS), we need to add the USD portion converted at the current rate.
        if ((!$booking->exchange_rate || $booking->exchange_rate == 1.000000) && $booking->total_amount_usd > 0) {
            $netAmt += round($booking->total_amount_usd * $bookingRate, 2);
        }
        $agent = null;
        $associate = null;

        if ($bookingUser->domain === 'agent') {
            $agent = \App\Models\Agent::where('user_id', $bookingUser->id)->first();
        } else {
            $associate = \App\Models\AssociateAgent::where('user_id', $bookingUser->id)->with('agent')->first();
            $agent = $associate?->agent;
        }

        if ($agent && $agent->account) {
            DB::transaction(function () use ($agent, $netAmt, $associate, $bookingUser, $actor, $booking) {
                $account = \App\Models\AgentAccount::where('id', $agent->account->id)->lockForUpdate()->firstOrFail();
                
                // Handle Agent Commission
                $commissionAmount = 0;
                if ($agent->getEffectiveCommissionMethod() === 'pre_deposit') {
                    $commissionRate = $agent->getEffectiveCommissionRate();
                    $commissionAmount = round(($netAmt * $commissionRate) / 100, 2);
                }
                $deductionAmount = max(0, $netAmt - $commissionAmount);

                if ($account->balance < $deductionAmount) {
                    throw new Exception("Insufficient wallet balance. You need TZS " . number_format($deductionAmount, 2) . " but your agency wallet only has TZS " . number_format($account->balance, 2) . ".");
                }

                if ($associate && $associate->daily_credit_limit > 0) {
                    $spentToday = \App\Models\SystemTransaction::where('user_id', $associate->user_id)
                        ->whereHas('paymentType', fn($q) => $q->where('slug', 'debit'))
                        ->whereDate('transaction_date', now()->toDateString())
                        ->sum('amount_tzs');
                        
                    if (($spentToday + $deductionAmount) > $associate->daily_credit_limit) {
                        throw new Exception("This booking exceeds your daily sales limit of TZS " . number_format($associate->daily_credit_limit, 2) . ".");
                    }
                }

                $account->balance -= $deductionAmount;
                $account->save();
                
                // Record actual wallet deduction
                $walletPaymentType = \App\Models\PaymentType::where('slug', 'wallet')->first();
                $deductionMethod = \App\Models\PaymentMethod::where('slug', 'agent-wallet-deduction')->first();
                $actorName = $actor?->full_name ?? $bookingUser->full_name ?? 'System';
                
                \App\Models\SystemTransaction::create([
                    'transaction_number' => 'TX-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                    'transaction_category_id' => 2, // ticket_sale
                    'payment_type_id' => $walletPaymentType?->id,
                    'amount' => $deductionAmount,
                    'currency_code' => 'TZS',
                    'exchange_rate' => 1.000000,
                    'amount_tzs' => $deductionAmount,
                    'payment_method_id' => $deductionMethod?->id,
                    'payment_reference' => $booking->booking_reference,
                    'booking_id' => $booking->id,
                    'user_id' => $bookingUser->id,
                    'transaction_status_id' => 4, // completed
                    'description' => 'Ticket Booking Deduction: ' . ($associate ? $associate->associate_name : $agent->agency_name) . ' (By: ' . $actorName . ')',
                ]);

                // Record Commission Transaction
                if ($commissionAmount > 0) {
                    $commCatId = \App\Models\TransactionCategory::getIdBySlug('commission') ?? 3;
                    $creditPaymentType = \App\Models\PaymentType::where('slug', 'credit')->first();
                    
                    \App\Models\SystemTransaction::create([
                        'transaction_number' => 'COMM-' . date('Ymd') . '-' . strtoupper(\Illuminate\Support\Str::random(6)),
                        'transaction_category_id' => $commCatId,
                        'payment_type_id' => $creditPaymentType?->id,
                        'amount' => $commissionAmount,
                        'currency_code' => 'TZS',
                        'exchange_rate' => 1.000000,
                        'amount_tzs' => $commissionAmount,
                        'payment_method_id' => $deductionMethod?->id,
                        'payment_reference' => $booking->booking_reference,
                        'booking_id' => $booking->id,
                        'user_id' => $bookingUser->id,
                        'transaction_status_id' => 4, // completed
                        'description' => 'Upfront Commission for Booking: ' . $booking->booking_reference . ' (By: ' . $actorName . ')',
                    ]);
                }
            });
        }
    }
}
