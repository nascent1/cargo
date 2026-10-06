<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\SystemTransaction;
use App\Models\AgentAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReconcileFinancialsCommand extends Command
{
    protected $signature = 'financials:reconcile
                            {--date= : Date to reconcile (YYYY-MM-DD, default: yesterday)}
                            {--fix : Attempt to auto-fix minor discrepancies}';

    protected $description = 'Daily financial reconciliation: compare booking amounts vs transaction records and flag discrepancies';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();
        $this->info("Running financial reconciliation for {$date}...");
        $discrepancies = [];

        // ── Check 1: Confirmed bookings with NO payment transaction ─────
        $this->info('Check 1: Confirmed bookings missing payment transactions...');
        $orphanedBookings = Booking::whereDate('created_at', $date)
            ->where('status', 'confirmed')
            ->whereDoesntHave('transactions')
            ->get(['id', 'booking_reference', 'net_amount', 'status', 'payment_status']);

        foreach ($orphanedBookings as $b) {
            $discrepancies[] = [
                'type' => 'MISSING_TX',
                'entity' => "Booking #{$b->id} ({$b->booking_reference})",
                'detail' => "Status: confirmed, Amount: {$b->net_amount}, but NO SystemTransaction found",
                'severity' => 'HIGH',
            ];
        }
        $this->line("  Found {$orphanedBookings->count()} orphaned booking(s).");

        // ── Check 2: Booking net_amount vs sum of transaction amounts ────
        $this->info('Check 2: Booking net_amount vs transaction sum...');
        $mismatchCount = 0;
        Booking::whereDate('created_at', $date)
            ->where('status', 'confirmed')
            ->whereHas('transactions')
            ->with('transactions')
            ->chunk(100, function ($bookings) use (&$discrepancies, &$mismatchCount) {
                foreach ($bookings as $booking) {
                    $txSum = (float) $booking->transactions
                        ->whereNotNull('amount')
                        ->sum('amount');
                    $netAmt = (float) $booking->net_amount;

                    // Allow 1 cent tolerance for rounding
                    if (abs($txSum - $netAmt) > 0.01) {
                        $discrepancies[] = [
                            'type' => 'AMOUNT_MISMATCH',
                            'entity' => "Booking #{$booking->id} ({$booking->booking_reference})",
                            'detail' => "net_amount={$netAmt}, TX sum={$txSum}, diff=" . round($txSum - $netAmt, 2),
                            'severity' => abs($txSum - $netAmt) > 100 ? 'HIGH' : 'MEDIUM',
                        ];
                        $mismatchCount++;
                    }
                }
            });
        $this->line("  Found {$mismatchCount} amount mismatch(es).");

        // ── Check 3: Agent balance vs TX history ────────────────────────
        $this->info('Check 3: Agent wallet balance vs transaction history...');
        $walletIssues = 0;
        AgentAccount::with('agent.user')->chunk(50, function ($accounts) use (&$discrepancies, &$walletIssues) {
            foreach ($accounts as $account) {
                $userId = $account->agent?->user_id;
                if (!$userId) continue;

                $txBalance = (float) SystemTransaction::where('user_id', $userId)
                    ->sum('amount');

                // Also include top-ups: these are positive amounts
                $currentBalance = (float) $account->balance;

                // Only flag if the difference is significant (> 1.00)
                if (abs($currentBalance - $txBalance) > 1.00) {
                    $agentName = $account->agent?->user?->name ?? "Account #{$account->id}";
                    $discrepancies[] = [
                        'type' => 'WALLET_MISMATCH',
                        'entity' => "Agent: {$agentName} (Account #{$account->id})",
                        'detail' => "DB balance={$currentBalance}, TX-derived balance={$txBalance}, diff=" . round($currentBalance - $txBalance, 2),
                        'severity' => abs($currentBalance - $txBalance) > 1000 ? 'HIGH' : 'MEDIUM',
                    ];
                    $walletIssues++;
                }
            }
        });
        $this->line("  Found {$walletIssues} wallet balance issue(s).");

        // ── Check 4: Duplicate transaction numbers ──────────────────────
        $this->info('Check 4: Duplicate transaction numbers...');
        $dupes = DB::table('transactions')
            ->select('transaction_number', DB::raw('COUNT(*) as cnt'))
            ->whereDate('created_at', $date)
            ->groupBy('transaction_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($dupes as $d) {
            $discrepancies[] = [
                'type' => 'DUPLICATE_TX',
                'entity' => "TX: {$d->transaction_number}",
                'detail' => "Found {$d->cnt} records with the same transaction_number",
                'severity' => 'HIGH',
            ];
        }
        $this->line("  Found {$dupes->count()} duplicate TX number(s).");

        // ── Output Report ───────────────────────────────────────────────
        $this->newLine();
        if (empty($discrepancies)) {
            $this->info('✅ No discrepancies found. Financial records are consistent.');
            Log::info("Financial reconciliation for {$date}: CLEAN — no discrepancies.");
            return self::SUCCESS;
        }

        $this->warn("⚠ Found " . count($discrepancies) . " discrepancy(ies):");
        $this->newLine();

        $highCount = collect($discrepancies)->where('severity', 'HIGH')->count();
        $medCount = collect($discrepancies)->where('severity', 'MEDIUM')->count();

        $this->table(
            ['Type', 'Entity', 'Detail', 'Severity'],
            collect($discrepancies)->map(fn($d) => [$d['type'], $d['entity'], $d['detail'], $d['severity']])->toArray()
        );

        $logMessage = "Financial reconciliation for {$date}: {$highCount} HIGH, {$medCount} MEDIUM discrepancies. Total: " . count($discrepancies);
        Log::warning($logMessage, ['discrepancies' => $discrepancies]);

        $this->newLine();
        $this->error("Summary: {$highCount} HIGH severity, {$medCount} MEDIUM severity");

        return $highCount > 0 ? self::FAILURE : self::SUCCESS;
    }
}
