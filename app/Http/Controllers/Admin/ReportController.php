<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Route;
use App\Services\FinancialReportService;
use App\Services\OperationalReportService;
use App\Services\AgentReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportController extends Controller
{
    protected FinancialReportService $financialReport;
    protected OperationalReportService $operationalReport;
    protected AgentReportService $agentReport;

    public function __construct(
        FinancialReportService $financialReport,
        OperationalReportService $operationalReport,
        AgentReportService $agentReport
    ) {
        $this->financialReport = $financialReport;
        $this->operationalReport = $operationalReport;
        $this->agentReport = $agentReport;
    }

    /**
     * Reports Hub (Index)
     */
    public function index()
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        return view('admin.reports.index');
    }

    /**
     * Revenue Report
     */
    public function revenue(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->only(['date_from', 'date_to', 'route_id', 'agent_user_id', 'group_by']);
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->financialReport->revenueReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Revenue_Report');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.revenue', compact('filters', 'data'), 'Revenue_Report');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.revenue', compact('filters', 'data'), 'Revenue_Report');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.revenue', compact('filters', 'data', 'routes', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Occupancy Report
     */
    public function occupancy(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->operationalReport->occupancyReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Occupancy_Report');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.occupancy', compact('filters', 'data'), 'Occupancy_Report');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.occupancy', compact('filters', 'data'), 'Occupancy_Report');
            }
        }
        
        $routes = Route::where('status', 'active')->get();
        return view('admin.reports.occupancy', compact('filters', 'data', 'routes'));
    }

    /**
     * Float Ledger Report
     */
    public function floatLedger(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view_all'); // Restrict to higher level
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->agentReport->floatReport($filters);
            if ($request->query('export') === 'excel') {
                $exportData = collect($data)->map(fn($row) => [
                    'Date' => $row['created_at'],
                    'Ref' => $row['transaction_reference'],
                    'User' => $row['user']['full_name'] ?? 'System',
                    'Category' => $row['category']['name'] ?? '',
                    'Amount' => $row['amount_tzs'],
                    'Status' => $row['status']['name'] ?? '',
                ])->toArray();
                return $this->exportExcel($exportData, 'Float_Ledger');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.float_ledger', compact('filters', 'data'), 'Float_Ledger');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.float_ledger', compact('filters', 'data'), 'Float_Ledger');
            }
        }
        
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();
        return view('admin.reports.float_ledger', compact('filters', 'data', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Agent Performance Report
     */
    public function agentPerformance(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view_all'); // Restrict to admin
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->agentReport->agentPerformanceReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Agent_Performance');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.agent_performance', compact('filters', 'data'), 'Agent_Performance');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.agent_performance', compact('filters', 'data'), 'Agent_Performance');
            }
        }
        
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();
        return view('admin.reports.agent_performance', compact('filters', 'data', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Helper to export to Excel (XLSX)
     */
    private function exportExcel(array $data, string $filenamePrefix)
    {
        $filename = $filenamePrefix . '_' . now()->format('Ymd_His') . '.xlsx';
        
        $writer = SimpleExcelWriter::streamDownload($filename);
        
        if (count($data) > 0) {
            foreach ($data as $row) {
                $writer->addRow((array) $row);
            }
        }
        
        return $writer->toBrowser();
    }

    /**
     * Helper to export to PDF
     */
    private function exportPdf(string $view, array $viewData, string $filenamePrefix, string $orientation = 'portrait')
    {
        $filename = $filenamePrefix . '_' . now()->format('Ymd_His') . '.pdf';
        
        // Pass a specific layout for PDF if needed
        $pdf = Pdf::loadView($view, $viewData)->setPaper('a4', $orientation);
        return $pdf->stream($filename);
    }

    /**
     * Helper to export to DOCX
     */
    private function exportDocx(string $view, array $viewData, string $filenamePrefix, string $orientation = 'portrait')
    {
        $filename = $filenamePrefix . '_' . now()->format('Ymd_His') . '.docx';
        $html = view($view, $viewData)->render();
        
        // PhpWord's HTML parser is very strict XML, we must sanitize the blade output
        $html = preg_replace('/<!DOCTYPE.*?>/is', '', $html);
        $html = preg_replace('/<html.*?>.*?<body.*?>/is', '', $html);
        $html = str_replace(['</body>', '</html>'], '', $html);
        $html = preg_replace('/<img[^>]*>/is', '', $html); // Remove img tags to prevent data-uri parsing errors
        $html = str_replace('<br>', '<br/>', $html);
        $html = str_replace('<hr>', '<hr/>', $html);
        $html = str_replace('&', '&amp;', str_replace('&amp;', '&', $html));
        
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        $section = $phpWord->addSection([
            'orientation' => $orientation
        ]);
        
        // Add company logo programmatically before the HTML content
        $companyLogo = \App\Models\SystemConfig::where('key_name', 'company_logo_url')->value('value') ?? '/images/logo.png';
        $logoPath = public_path(ltrim($companyLogo, '/'));
        if (file_exists($logoPath)) {
            $section->addImage($logoPath, [
                'height' => 45,
                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER
            ]);
        }
        
        try {
            \PhpOffice\PhpWord\Shared\Html::addHtml($section, $html, false, false);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('PhpWord addHtml failed: ' . $e->getMessage());
            $section->addText("Could not render the report to DOCX format perfectly. Please use Excel or PDF.");
        }
        
        $tempFile = tempnam(sys_get_temp_dir(), 'docx');
        $phpWord->save($tempFile, 'Word2007');
        
        return response()->download($tempFile, $filename)->deleteFileAfterSend(true);
    }
    /**
     * Booking Report
     */
    public function bookings(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->financialReport->bookingReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Booking_Report');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.bookings', compact('filters', 'data'), 'Booking_Report', 'landscape');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.bookings', compact('filters', 'data'), 'Booking_Report', 'landscape');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.bookings', compact('filters', 'data', 'routes', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Manifest Report
     */
    public function manifest(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run') && !empty($filters['departure_date']) && !empty($filters['route_id']) && !empty($filters['departure_time']) && !empty($filters['ferry_id'])) {
            $data = $this->operationalReport->manifestReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Passenger_Manifest');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.manifest', compact('filters', 'data'), 'Passenger_Manifest');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.manifest', compact('filters', 'data'), 'Passenger_Manifest');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $ferries = \App\Models\Ferry::where('status', 'active')->get();

        return view('admin.reports.manifest', compact('filters', 'data', 'routes', 'ferries'));
    }

    /**
     * Manifest Cascading Options (AJAX)
     */
    public function manifestOptions(Request $request)
    {
        $date = $request->query('date');
        $route_id = $request->query('route_id');
        $time = $request->query('time');
        $fetch = $request->query('fetch');

        $query = \App\Models\Voyage::query();

        if ($date) {
            $query->where('departure_date', $date);
        }

        if ($fetch === 'routes') {
            $routeIds = $query->pluck('route_id')->unique();
            $routes = \App\Models\Route::whereIn('id', $routeIds)->get(['id', 'name']);
            return response()->json(['routes' => $routes]);
        }

        if ($fetch === 'times' && $route_id) {
            $times = $query->where('route_id', $route_id)
                           ->pluck('departure_time')
                           ->unique()
                           ->map(fn($t) => \Carbon\Carbon::parse($t)->format('H:i'))
                           ->values();
            return response()->json(['times' => $times]);
        }

        if ($fetch === 'ferries' && $route_id && $time) {
            $ferryIds = $query->where('route_id', $route_id)
                              ->whereTime('departure_time', '=', $time)
                              ->pluck('ferry_id')
                              ->unique();
            $ferries = \App\Models\Ferry::whereIn('id', $ferryIds)->get(['id', 'name']);
            return response()->json(['ferries' => $ferries]);
        }

        return response()->json([]);
    }

    /**
     * Boarding Report
     */
    public function boarding(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->only(['date_from', 'date_to', 'route_id']);
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->operationalReport->boardingReport($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Boarding_Report');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.boarding', compact('filters', 'data'), 'Boarding_Report');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.boarding', compact('filters', 'data'), 'Boarding_Report');
            }
        }
        
        $routes = Route::where('status', 'active')->get();
        return view('admin.reports.boarding', compact('filters', 'data', 'routes'));
    }

    /**
     * Predictive Analytics & Projections
     */
    public function projections()
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        
        $data = $this->operationalReport->predictiveAnalytics();
        
        return view('admin.reports.projections', compact('data'));
    }

    /**
     * Payment Channel Analysis
     */
    public function paymentAnalysis(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->financialReport->paymentChannelAnalysis($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Payment_Channel_Analysis');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.payment_analysis', compact('filters', 'data'), 'Payment_Channel_Analysis');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.payment_analysis', compact('filters', 'data'), 'Payment_Channel_Analysis');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.payment_analysis', compact('filters', 'data', 'routes', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Peak Hour Analysis
     */
    public function peakHours(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->financialReport->peakHourAnalysis($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Peak_Hour_Analysis');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.peak_hours', compact('filters', 'data'), 'Peak_Hour_Analysis');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.peak_hours', compact('filters', 'data'), 'Peak_Hour_Analysis');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.peak_hours', compact('filters', 'data', 'routes', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Route Profitability Report
     */
    public function routeProfitability(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->operationalReport->routeProfitability($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Route_Profitability');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.route_profitability', compact('filters', 'data'), 'Route_Profitability', 'landscape');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.route_profitability', compact('filters', 'data'), 'Route_Profitability', 'landscape');
            }
        }

        $routes = Route::where('status', 'active')->get();
        return view('admin.reports.route_profitability', compact('filters', 'data', 'routes'));
    }

    /**
     * Cancellation Trends Report
     */
    public function cancellationTrends(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->operationalReport->cancellationTrends($filters);
            if ($request->query('export') === 'excel') {
                $exportData = $data['daily'] ?? [];
                return $this->exportExcel($exportData, 'Cancellation_Trends');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.cancellation_trends', compact('filters', 'data'), 'Cancellation_Trends');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.cancellation_trends', compact('filters', 'data'), 'Cancellation_Trends');
            }
        }

        $routes = Route::where('status', 'active')->get();
        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.cancellation_trends', compact('filters', 'data', 'routes', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * Commission Reconciliation Report
     */
    public function commissionReconciliation(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view_all');
        $filters = $request->all();
        
        $data = [];
        if ($request->has('run')) {
            $data = $this->agentReport->commissionReconciliation($filters);
            if ($request->query('export') === 'excel') {
                return $this->exportExcel($data, 'Commission_Reconciliation');
            }
            if ($request->query('export') === 'pdf') {
                return $this->exportPdf('admin.reports.pdf.commission_reconciliation', compact('filters', 'data'), 'Commission_Reconciliation', 'landscape');
            }
            if ($request->query('export') === 'docx') {
                return $this->exportDocx('admin.reports.pdf.commission_reconciliation', compact('filters', 'data'), 'Commission_Reconciliation', 'landscape');
            }
        }

        $agents = Agent::where('is_active', true)->get();
        $systemUsers = \App\Models\SystemUser::with('user')->get();
        $agentsWithAssociates = Agent::where('is_active', true)->with('user', 'associates.user')->get();

        return view('admin.reports.commission_reconciliation', compact('filters', 'data', 'agents', 'systemUsers', 'agentsWithAssociates'));
    }

    /**
     * System Financial Reconciliation
     */
    public function systemReconciliation(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('report:view');
        
        $date = $request->input('date', now()->toDateString());
        $discrepancies = [];
        
        if ($request->has('run')) {
            // Check 1: Missing Tx
            $orphanedBookings = \App\Models\Booking::whereDate('created_at', $date)
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

            // Check 2: Amount Mismatch
            \App\Models\Booking::whereDate('created_at', $date)
                ->where('status', 'confirmed')
                ->whereHas('transactions')
                ->with('transactions')
                ->chunk(100, function ($bookings) use (&$discrepancies) {
                    foreach ($bookings as $booking) {
                        $txSum = (float) $booking->transactions->whereNotNull('amount')->sum('amount');
                        $netAmt = (float) $booking->net_amount;
                        if (abs($txSum - $netAmt) > 0.01) {
                            $discrepancies[] = [
                                'type' => 'AMOUNT_MISMATCH',
                                'entity' => "Booking #{$booking->id} ({$booking->booking_reference})",
                                'detail' => "net_amount={$netAmt}, TX sum={$txSum}, diff=" . round($txSum - $netAmt, 2),
                                'severity' => abs($txSum - $netAmt) > 100 ? 'HIGH' : 'MEDIUM',
                            ];
                        }
                    }
                });

            // Check 3: Agent Wallet vs TX
            \App\Models\AgentAccount::with('agent.user')->chunk(50, function ($accounts) use (&$discrepancies) {
                foreach ($accounts as $account) {
                    $userId = $account->agent?->user_id;
                    if (!$userId) continue;
                    $txBalance = (float) \App\Models\SystemTransaction::where('user_id', $userId)->sum('amount');
                    $currentBalance = (float) $account->balance;
                    if (abs($currentBalance - $txBalance) > 1.00) {
                        $agentName = $account->agent?->user?->name ?? "Account #{$account->id}";
                        $discrepancies[] = [
                            'type' => 'WALLET_MISMATCH',
                            'entity' => "Agent: {$agentName}",
                            'detail' => "DB balance={$currentBalance}, TX sum={$txBalance}, diff=" . round($currentBalance - $txBalance, 2),
                            'severity' => abs($currentBalance - $txBalance) > 1000 ? 'HIGH' : 'MEDIUM',
                        ];
                    }
                }
            });

            // Check 4: Duplicate TX numbers
            $dupes = \Illuminate\Support\Facades\DB::table('system_transactions')
                ->select('transaction_number', \Illuminate\Support\Facades\DB::raw('COUNT(*) as cnt'))
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

            // Check 5: Stale pending refunds (> 7 days old)
            $staleRefunds = \App\Models\PendingRefund::where('status', 'pending')
                ->where('created_at', '<', now()->subDays(7))
                ->get(['id', 'booking_id', 'amount', 'created_at']);

            foreach ($staleRefunds as $sr) {
                $age = $sr->created_at->diffInDays(now());
                $discrepancies[] = [
                    'type' => 'STALE_REFUND',
                    'entity' => "Pending Refund #{$sr->id} (Booking #{$sr->booking_id})",
                    'detail' => "Pending for {$age} days. Amount: " . number_format((float) $sr->amount, 2) . " TZS",
                    'severity' => $age > 14 ? 'HIGH' : 'MEDIUM',
                ];
            }
        }

        $totalChecks = $request->has('run') ? 5 : 0;
        $issueCount = count($discrepancies);
        $passCount = $totalChecks - ($issueCount > 0 ? min($issueCount, $totalChecks) : 0);

        return view('admin.reports.system-reconciliation', compact('date', 'discrepancies', 'totalChecks', 'issueCount', 'passCount'));
    }
}
