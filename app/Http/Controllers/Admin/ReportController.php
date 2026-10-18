<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Consignment;
use App\Models\PaymentMethod;
use App\Models\Route;
use App\Models\TransportUnit;
use App\Models\User;
use App\Services\Cargo\CargoReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\ExcelDownload;

/**
 * Cargo reports hub. Viewing needs cargo-report (route middleware); exporting needs cargo-export-report.
 */
class ReportController extends Controller
{
    public function __construct(protected CargoReportService $reports)
    {
    }

    public function index()
    {
        return view('admin.reports.index', ['reports' => $this->reports->definitions()]);
    }

    public function show(Request $request, string $report)
    {
        $definitions = $this->reports->definitions();
        abort_unless(isset($definitions[$report]), 404);
        $definition = $definitions[$report];

        $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $filters = array_merge(
            $this->reports->defaultFilters($report),
            array_filter($request->only([
                'date_from', 'date_to', 'date_field', 'group_by', 'route_id', 'transport_unit_id',
                'status', 'payment_status', 'payment_method', 'booked_by',
            ]), fn ($v) => $v !== null && $v !== '')
        );

        if ($format = $request->query('export')) {
            abort_unless($request->user()->hasPermission('cargo-export-report'), 403, 'You do not have permission to export reports.');

            return $this->export($report, $definition, $filters, $format);
        }

        return view('admin.reports.cargo.show', [
            'key' => $report,
            'definition' => $definition,
            'filters' => $filters,
            'result' => $this->reports->run($report, $filters, true),
            'options' => $this->filterOptions($definition),
            'canExport' => $request->user()->hasPermission('cargo-export-report'),
            'reports' => $definitions,
        ]);
    }

    private function export(string $report, array $definition, array $filters, string $format)
    {
        $result = $this->reports->run($report, $filters);
        $name = Str::studly($report) . '_' . ($filters['date_from'] ?? now()->toDateString()) . '_' . ($filters['date_to'] ?? '') . '_' . now()->format('His');

        if ($format === 'excel') {
            // The main table: the last section is the detail for multi-table reports (e.g. collections)
            $section = $report === 'collections' ? $result['sections'][1] : $result['sections'][0];
            return ExcelDownload::make($name . '.xlsx', function ($writer) use ($section) {
                foreach ($this->reports->exportRows($section) as $row) {
                    $writer->addRow($row);
                }
            });
        }

        abort_unless($format === 'pdf', 404);

        return Pdf::loadView('admin.reports.cargo.pdf', [
            'definition' => $definition,
            'filters' => $filters,
            'result' => $result,
            'filterSummary' => $this->filterSummary($definition, $filters),
        ])->setPaper('a4', $definition['orientation'] ?? 'portrait')->stream($name . '.pdf');
    }

    /**
     * Dropdown options for the filters this report uses.
     */
    private function filterOptions(array $definition): array
    {
        $uses = fn ($f) => in_array($f, $definition['filters'], true);

        return [
            'routes' => $uses('route_id') ? Route::orderBy('name')->pluck('name', 'id') : collect(),
            'units' => $uses('transport_unit_id') ? TransportUnit::orderBy('name')->pluck('name', 'id') : collect(),
            'paymentMethods' => $uses('payment_method') ? PaymentMethod::orderBy('sort_order')->pluck('name', 'slug') : collect(),
            'staff' => $uses('booked_by')
                ? User::whereIn('id', Consignment::select('booked_by_user_id')->whereNotNull('booked_by_user_id')->distinct())
                    ->orderBy('first_name')->get()->mapWithKeys(fn ($u) => [$u->id => $u->full_name])
                : collect(),
            'statuses' => ['pending_payment' => 'Awaiting payment', 'booked' => 'Booked', 'loaded' => 'Loaded', 'in_transit' => 'In transit',
                'delivered' => 'Delivered', 'unclaimed' => 'Unclaimed', 'collected' => 'Collected', 'cancelled' => 'Cancelled'],
            'attemptStatuses' => ['paid' => 'Paid', 'awaiting_customer' => 'Awaiting customer', 'initiated' => 'Started', 'sending' => 'Sending',
                'failed' => 'Failed', 'expired' => 'Expired', 'cancelled' => 'Cancelled', 'refund_due' => 'Refund due'],
        ];
    }

    /**
     * "1 Oct 2026 – 6 Oct 2026 · Route: … · Grouped by: …" for the PDF header.
     */
    private function filterSummary(array $definition, array $filters): string
    {
        $parts = [];
        if (in_array('dates', $definition['filters'], true) && (!empty($filters['date_from']) || !empty($filters['date_to']))) {
            $fmt = fn ($d) => $d ? \Carbon\Carbon::parse($d)->format('d M Y') : '…';
            $parts[] = $fmt($filters['date_from'] ?? null) . ' – ' . $fmt($filters['date_to'] ?? null)
                . (($filters['date_field'] ?? '') === 'departure' ? ' (departure date)' : '');
        }
        if (!empty($filters['group_by']) && isset($definition['group_by'][$filters['group_by']])) {
            $parts[] = 'Grouped by ' . strtolower($definition['group_by'][$filters['group_by']]);
        }
        if (!empty($filters['route_id'])) {
            $parts[] = 'Route: ' . (Route::find($filters['route_id'])?->name ?? $filters['route_id']);
        }
        if (!empty($filters['transport_unit_id'])) {
            $parts[] = 'Vessel: ' . (TransportUnit::find($filters['transport_unit_id'])?->name ?? $filters['transport_unit_id']);
        }
        foreach (['status' => 'Status', 'payment_status' => 'Payment', 'payment_method' => 'Channel'] as $key => $label) {
            if (!empty($filters[$key])) {
                $parts[] = $label . ': ' . Str::headline($filters[$key]);
            }
        }
        if (!empty($filters['booked_by'])) {
            $parts[] = 'Staff: ' . (User::find($filters['booked_by'])?->full_name ?? $filters['booked_by']);
        }

        return implode(' · ', $parts);
    }
}
