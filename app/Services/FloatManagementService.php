<?php

namespace App\Services;

use App\Models\AgentAccount;
use App\Models\Agent;
use App\Models\SystemTransaction;
use App\Models\SystemConfig;
use App\Models\User;
use App\Models\Booking;
use Illuminate\Support\Facades\DB;
use Exception;

class FloatManagementService
{
    protected SystemTransactionService $txService;
    protected DualControlService $dcService;

    public function __construct(SystemTransactionService $txService, DualControlService $dcService)
    {
        $this->txService = $txService;
        $this->dcService = $dcService;
    }

    /**
     * Request a top-up for an agent account.
     * Triggers Dual Control Maker-Checker if amount exceeds configured threshold (e.g., > 5,000,000 TZS).
     */
    public function requestTopup(AgentAccount $account, float $amount, string $paymentReference, ?User $actor = null): mixed
    {
        if ($amount <= 0) {
            throw new Exception("Top-up amount must be greater than zero.");
        }

        $threshold = (float) SystemConfig::getValue('dual_control_float_threshold', 5000000);

        if (($amount > $threshold && $this->dcService->requiresDualControl('float_topup')) || $this->dcService->requiresDualControl('agent_topup')) {
            // Submit Maker-Checker request
            return $this->dcService->submit('float_topup', 'agent_account', $account->id, [
                'before' => ['balance' => $account->balance],
                'after'  => [
                    'amount'    => $amount,
                    'reference' => $paymentReference,
                    'actor_id'  => $actor?->id,
                ],
            ]);
        }

        return $this->executeTopup($account, $amount, $paymentReference, $actor?->id);
    }

    /**
     * Execute an approved or below-threshold float top-up atomically (`SELECT FOR UPDATE`).
     */
    public function executeTopup(AgentAccount $account, float $amount, string $paymentReference = '', ?int $actorUserId = null, ?int $approverUserId = null): SystemTransaction
    {
        return DB::transaction(function () use ($account, $amount, $paymentReference, $actorUserId, $approverUserId) {
            // Lock account row for atomic balance calculation
            $lockedAccount = AgentAccount::where('id', $account->id)->lockForUpdate()->firstOrFail();

            $agent = $lockedAccount->agent;
            $agentUserId = $agent->user_id;

            // 1. Calculate Security Deposit split (40% hold by default)
            $secRate = $agent->getEffectiveSecurityDepositRate();
            $securityHoldAmount = $secRate > 0 ? round(($amount * $secRate) / 100, 4) : 0.0;
            $activeFloatAmount  = round($amount - $securityHoldAmount, 4);

            $balanceBefore = (float) $lockedAccount->balance;
            $balanceAfter  = round($balanceBefore + $activeFloatAmount, 4);

            $secBalanceBefore = (float) $lockedAccount->security_deposit_balance;
            $secBalanceAfter  = round($secBalanceBefore + $securityHoldAmount, 4);

            $lockedAccount->update([
                'balance' => $balanceAfter,
                'security_deposit_balance' => $secBalanceAfter,
            ]);

            // Record main float top-up (Active portion)
            $topupCatId = \App\Models\TransactionCategory::getIdBySlug('float-top-up', 1);
            $mainTx = $this->txService->recordTransaction([
                'transaction_category_id' => $topupCatId,
                'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                'payment_type_id'         => \App\Models\PaymentType::where('slug', 'credit')->value('id'),
                'amount'                  => $activeFloatAmount,
                'currency_code'           => $lockedAccount->currency ?? 'TZS',
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'payment_reference'       => !empty($paymentReference) ? $paymentReference : 'TOPUP-' . mt_rand(100000, 999999),
                'user_id'                 => $agentUserId,
                'description'             => "Float top-up (" . (100 - $secRate) . "% active float) for Account #{$lockedAccount->account_number}",
                'metadata'                => [
                    'total_deposit' => $amount,
                    'security_hold' => $securityHoldAmount,
                    'requested_by'  => $actorUserId,
                    'approved_by'   => $approverUserId,
                ],
            ]);

            // Record Security Deposit hold if applicable
            if ($securityHoldAmount > 0) {
                $adjCatId = \App\Models\TransactionCategory::getIdBySlug('adjustment-credit-or-debit', 6);
                $this->txService->recordTransaction([
                    'transaction_category_id' => $adjCatId,
                    'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                    'payment_type_id'         => \App\Models\PaymentType::where('slug', 'credit')->value('id'),
                    'amount'                  => $securityHoldAmount,
                    'currency_code'           => $lockedAccount->currency ?? 'TZS',
                    'balance_before'          => $secBalanceBefore,
                    'balance_after'           => $secBalanceAfter,
                    'payment_reference'       => 'SEC-HOLD-' . (!empty($paymentReference) ? $paymentReference : mt_rand(100000, 999999)),
                    'user_id'                 => $agentUserId,
                    'description'             => "Security Deposit Reserve Hold ({$secRate}%) from deposit of " . number_format($amount, 2) . " TZS",
                    'metadata'                => ['security_rate' => $secRate, 'original_deposit' => $amount],
                ]);
            }

            // 2. Check and calculate Upfront Pre-Deposit Percentage Commission (%)
            if ($agent->getEffectiveCommissionMethod() === 'pre_deposit') {
                $commRate = $agent->getEffectiveCommissionRate();
                if ($commRate > 0) {
                    $commAmount = round(($amount * $commRate) / 100, 4);
                    $balBeforeComm = (float) $lockedAccount->balance;
                    $balAfterComm  = round($balBeforeComm + $commAmount, 4);
                    $lockedAccount->update(['balance' => $balAfterComm]);

                    $commCatId = \App\Models\TransactionCategory::getIdBySlug('commission', 5);
                    $this->txService->recordTransaction([
                        'transaction_category_id' => $commCatId,
                        'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                        'payment_type_id'         => \App\Models\PaymentType::where('slug', 'credit')->value('id'),
                        'amount'                  => $commAmount,
                        'currency_code'           => $lockedAccount->currency ?? 'TZS',
                        'balance_before'          => $balBeforeComm,
                        'balance_after'           => $balAfterComm,
                        'payment_reference'       => 'PRE-COMM-' . (!empty($paymentReference) ? $paymentReference : mt_rand(100000, 999999)),
                        'user_id'                 => $agentUserId,
                        'description'             => "Upfront Commission ({$commRate}%) on Top-Up of " . number_format($amount, 2) . " TZS",
                        'metadata'                => ['commission_method' => 'pre_deposit', 'commission_rate' => $commRate],
                    ]);
                }
            }

            // 3. Optional: Trigger Webhook or Notification
            // TODO: dispatch FloatTopupCompleted event

            // Send SMS to the agent
            if ($agent && $agent->user && !empty($agent->user->phone)) {
                $sms = new \App\Services\SmsService();
                $formattedAmount = number_format($amount, 2);
                $sms->sendSms($agent->user->phone, "Your float deposit of TZS {$formattedAmount} has been approved and deposited. Active Float: " . number_format($activeFloatAmount, 2));
            }

            return $mainTx;
        });
    }

    /**
     * Deduct float for ticket booking sale.
     */
    public function deductForTicketSale(AgentAccount $account, Booking $booking, float $amount): SystemTransaction
    {
        return DB::transaction(function () use ($account, $booking, $amount) {
            $lockedAccount = AgentAccount::where('id', $account->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $lockedAccount->balance;
            $creditLimit   = (float) $lockedAccount->credit_limit;
            $available     = round($balanceBefore + $creditLimit, 4);

            if ($available < $amount) {
                throw new Exception("Error 402: Insufficient agent float balance. Available funds: {$available} {$lockedAccount->currency}, required: {$amount} {$lockedAccount->currency}.");
            }

            $balanceAfter = round($balanceBefore - $amount, 4);

            $lockedAccount->update([
                'balance' => $balanceAfter,
            ]);

            $agentUserId = $lockedAccount->agent->user_id;

            return $this->txService->recordTransaction([
                'transaction_category_id' => 2, // ticket-sale
                'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                'payment_type_id'         => \App\Models\PaymentType::where('slug', 'debit')->value('id'),
                'amount'                  => $amount,
                'currency_code'           => $lockedAccount->currency ?? 'TZS',
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'booking_id'              => $booking->id,
                'user_id'                 => $agentUserId,
                'description'             => "Float deduction for booking #{$booking->booking_reference}",
            ]);
        });
    }

    /**
     * Credit refund back to agent float after ticket cancellation.
     */
    public function creditRefund(AgentAccount $account, Booking $booking, float $amount, string $reason = '', ?int $ticketId = null): SystemTransaction
    {
        return DB::transaction(function () use ($account, $booking, $amount, $reason, $ticketId) {
            $lockedAccount = AgentAccount::where('id', $account->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = (float) $lockedAccount->balance;
            $balanceAfter  = round($balanceBefore + $amount, 4);

            $lockedAccount->update([
                'balance' => $balanceAfter,
            ]);

            $agentUserId = $lockedAccount->agent->user_id;

            return $this->txService->recordTransaction([
                'transaction_category_id' => 5, // refund
                'transaction_status_id'   => \App\Models\TransactionStatus::getIdBySlug('completed', 4),
                'payment_type_id'         => \App\Models\PaymentType::where('slug', 'reverse')->value('id'),
                'amount'                  => $amount,
                'currency_code'           => $lockedAccount->currency ?? 'TZS',
                'balance_before'          => $balanceBefore,
                'balance_after'           => $balanceAfter,
                'booking_id'              => $booking->id,
                'ticket_id'               => $ticketId,
                'user_id'                 => $agentUserId,
                'description'             => "Refund to float for booking #{$booking->booking_reference}: {$reason}",
            ]);
        });
    }

    /**
     * Update credit limit for an agent account.
     */
    public function updateCreditLimit(AgentAccount $account, float $newCreditLimit): AgentAccount
    {
        return DB::transaction(function () use ($account, $newCreditLimit) {
            if ($newCreditLimit < 0) {
                throw new Exception("Credit limit cannot be negative.");
            }

            $account->update(['credit_limit' => round($newCreditLimit, 4)]);
            return $account->fresh();
        });
    }
}
