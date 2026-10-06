<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ReportSnapshot;
use App\Services\FinancialReportService;
use App\Services\AgentReportService;
use Carbon\Carbon;

class GenerateReportSnapshots extends Command
{
    protected $signature = 'reports:snapshot {--date= : The date to snapshot (Y-m-d). Defaults to yesterday.}';
    protected $description = 'Generates and archives heavy report data into report_snapshots table for performance.';

    public function handle(FinancialReportService $financialReport, AgentReportService $agentReport)
    {
        $dateStr = $this->option('date') ?? Carbon::yesterday()->toDateString();
        $this->info("Generating report snapshots for {$dateStr}...");

        $filters = [
            'date_from' => $dateStr,
            'date_to'   => $dateStr,
        ];

        // 1. Revenue Snapshot
        $this->info("-> Running Revenue Snapshot");
        $revenueData = $financialReport->revenueReport(array_merge($filters, ['group_by' => 'route']));
        ReportSnapshot::updateOrCreate(
            ['snapshot_date' => $dateStr, 'report_type' => 'revenue_by_route'],
            ['report_data' => $revenueData]
        );

        // 2. Sales Summary Snapshot
        $this->info("-> Running Sales Summary Snapshot");
        $salesData = $financialReport->salesSummary($filters);
        ReportSnapshot::updateOrCreate(
            ['snapshot_date' => $dateStr, 'report_type' => 'sales_summary'],
            ['report_data' => $salesData]
        );

        // 3. Float Snapshot (Agent balances)
        $this->info("-> Running Float Ledger Snapshot");
        $floatData = $agentReport->floatReport($filters);
        ReportSnapshot::updateOrCreate(
            ['snapshot_date' => $dateStr, 'report_type' => 'float_ledger'],
            ['report_data' => $floatData]
        );

        $this->info("✅ Snapshots for {$dateStr} created successfully.");
        return Command::SUCCESS;
    }
}
