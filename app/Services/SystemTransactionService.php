<?php

namespace App\Services;

use App\Models\SystemTransaction;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Exception;

class SystemTransactionService
{
    /**
     * Record a new transaction in the centralized system_transactions ledger.
     * This method is thread-safe and should typically run within a database transaction.
     */
    public function recordTransaction(array $data): SystemTransaction
    {
        return DB::transaction(function () use ($data) {
            $amount = (float) ($data['amount'] ?? 0);
            if ($amount <= 0) {
                throw new Exception("Transaction amount must be greater than zero.");
            }

            $currency = strtoupper($data['currency_code'] ?? 'TZS');
            $exchangeRate = (float) ($data['exchange_rate'] ?? ($currency === 'TZS' ? 1.0 : 2600.0));
            $amountTzs = round($amount * $exchangeRate, 2);

            $txNumber = strtoupper($data['transaction_number'] ?? ('TX-' . date('Ymd') . '-' . Str::random(6)));
            while (SystemTransaction::where('transaction_number', $txNumber)->exists()) {
                $txNumber = 'TX-' . date('Ymd') . '-' . strtoupper(Str::random(6));
            }

            // Resolve lookup IDs
            $categoryId = $data['transaction_category_id'] ?? null;
            if (!$categoryId && isset($data['category'])) {
                $categoryId = \App\Models\TransactionCategory::where('slug', $data['category'])->value('id');
            }
            $categoryId = $categoryId ?: 2; // Default to ticket-sale (2)

            $statusId = $data['transaction_status_id'] ?? null;
            if (!$statusId && isset($data['status'])) {
                $statusId = \App\Models\TransactionStatus::where('slug', $data['status'])->value('id');
            }
            $statusId = $statusId ?: 4; // Default to completed (4)

            return SystemTransaction::create([
                'transaction_number'      => $txNumber,
                'transaction_category_id' => $categoryId,
                'transaction_status_id'   => $statusId,
                'payment_type_id'         => $data['payment_type_id'] ?? null,
                'amount'                  => $amount,
                'currency_code'           => $currency,
                'exchange_rate'           => $exchangeRate,
                'amount_tzs'              => $amountTzs,
                'balance_before'          => isset($data['balance_before']) ? (float) $data['balance_before'] : null,
                'balance_after'           => isset($data['balance_after']) ? (float) $data['balance_after'] : null,
                'payment_method_id'       => $data['payment_method_id'] ?? null,
                'payment_reference'       => $data['payment_reference'] ?? null,
                'booking_id'              => $data['booking_id'] ?? null,
                'ticket_id'         => $data['ticket_id'] ?? null,
                'consignment_id'          => $data['consignment_id'] ?? null,
                'user_id'                 => $data['user_id'] ?? null,
                'description'             => $data['description'] ?? null,
                'metadata'                => $data['metadata'] ?? null,
            ]);
        });
    }

    /**
     * Get all transactions for a specific user.
     */
    public function getByUser(int $userId, ?string $category = null, int $perPage = 20)
    {
        $query = SystemTransaction::where('user_id', $userId)->orderByDesc('id');

        if ($category) {
            $query->where('category', $category);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get platform ledger summary within a date range grouped by category.
     */
    public function getLedgerSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $query = SystemTransaction::where('status', 'completed');

        if ($startDate) {
            $query->whereDate('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('created_at', '<=', $endDate);
        }

        $grouped = $query->selectRaw('transaction_category_id, payment_type_id, COUNT(*) as tx_count, SUM(amount_tzs) as total_tzs')
            ->groupBy('transaction_category_id', 'payment_type_id')
            ->get();

        return $grouped->toArray();
    }
}
