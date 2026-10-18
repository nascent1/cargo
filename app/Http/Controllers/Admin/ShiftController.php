<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Consignment;
use App\Models\DualControlRequest;
use App\Models\Shift;
use App\Models\User;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use App\Services\ShiftService;
use App\Support\ExcelDownload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Operations › Shifts: who is on shift now, shift history with cash handover figures,
 * ending a shift and requesting an extension (dual control: shift_extension), Excel export.
 */
class ShiftController extends Controller
{
    private const FILTERS = [
        'user_id' => 'nullable|integer',
        'from' => 'nullable|date',
        'to' => 'nullable|date|after_or_equal:from',
        'status' => 'nullable|in:active,ended',
    ];

    public function __construct(private ShiftService $shifts)
    {
    }

    public function index(Request $request)
    {
        $filters = $request->validate(self::FILTERS);
        $this->shifts->closeExpired();

        $onShift = Shift::with('user')->active()->orderBy('end_time')->get();
        $history = $this->filtered($filters)->with(['user', 'endedBy'])->paginate(25)->withQueryString();
        $totals = $this->shifts->totalsFor($onShift->concat($history->getCollection()));
        $pending = DualControlRequest::where('operation_type', 'shift_extension')->whereIn('status', ['pending', 'scheduled'])
            ->get()->keyBy('resource_id');

        return view('admin.shifts.index', [
            'onShift' => $onShift,
            'history' => $history,
            'totals' => $totals,
            'pending' => $pending,
            'clerks' => User::whereIn('id', Shift::select('user_id')->distinct())->orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'filters' => $filters,
            'settings' => $this->settings(),
            'canManage' => $request->user()->hasPermission('shift:manage'),
        ]);
    }

    public function show(Request $request, Shift $shift)
    {
        $shift->load(['user', 'endedBy']);

        return view('admin.shifts.show', [
            'shift' => $shift,
            'summary' => $this->shifts->summary($shift),
            'bookings' => Consignment::where('booked_by_user_id', $shift->user_id)
                ->where('created_at', '>=', $shift->start_time)->where('created_at', '<=', $shift->periodEnd())
                ->orderBy('created_at')->get(),
            'extensions' => DualControlRequest::with(['maker', 'checker'])->where('subject_key', $shift->subjectKey())->latest('id')->get(),
            'pending' => $this->shifts->pendingExtension($shift),
            'settings' => $this->settings(),
            'canManage' => $request->user()->hasPermission('shift:manage'),
        ]);
    }

    public function end(Request $request, Shift $shift): RedirectResponse
    {
        if (!$shift->isActive()) {
            return back()->with('error', 'This shift has already ended.');
        }

        $this->shifts->endShift($shift, Shift::END_ADMIN, $request->user()->id);
        AuditLog::createEntry($request->user()->id, 'shift_end', 'shift', $shift->id, null,
            ['end_reason' => Shift::END_ADMIN, 'clerk_id' => $shift->user_id], $request->ip());

        $nextFrom = $this->shifts->nextShiftFrom($shift)?->format('d M H:i');

        return back()->with('success', "{$shift->user?->full_name}'s shift has ended. They can't book or take payments"
            . ($nextFrom ? " until {$nextFrom}" : '') . ', unless an extension is approved.');
    }

    public function extend(Request $request, Shift $shift, DualControlService $dualControl): RedirectResponse
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.5|max:12',
            'reason' => 'required|string|max:255',
        ]);

        try {
            $dcRequest = $dualControl->submit('shift_extension', 'shift', $shift->id, [
                'before' => [
                    'clerk' => $shift->user?->full_name,
                    'started_at' => $shift->start_time->format('d M Y H:i'),
                    'ends_at' => $shift->end_time?->format('d M Y H:i'),
                    'status' => $shift->isActive() ? 'on shift' : 'ended',
                    'extended_minutes' => (int) $shift->extended_minutes,
                ],
                'after' => ['hours' => (float) $validated['hours'], 'reason' => $validated['reason']],
            ], ['subject_key' => $shift->subjectKey()]);
        } catch (DualControlException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $dualControl->outcomeMessage($dcRequest, "{$shift->user?->full_name}'s shift was extended by {$validated['hours']} h."));
    }

    public function export(Request $request)
    {
        $filters = $request->validate(self::FILTERS);
        $this->shifts->closeExpired();
        $shifts = $this->filtered($filters)->with(['user', 'endedBy'])->limit(5000)->get();
        $totals = $this->shifts->totalsFor($shifts);
        $money = fn (array $byCurrency, string $currency) => (float) ($byCurrency[$currency] ?? 0);

        return ExcelDownload::make('Shifts_' . now()->format('Ymd_His') . '.xlsx', function ($writer) use ($shifts, $totals, $money) {
            foreach ($shifts as $s) {
                $t = $totals[$s->id] ?? ['bookings' => 0, 'collected' => [], 'cash' => []];
                $writer->addRow([
                    'Shift #' => $s->id,
                    'Clerk' => $s->user?->full_name,
                    'Started' => $s->start_time->format('Y-m-d H:i'),
                    'Planned End' => $s->end_time?->format('Y-m-d H:i'),
                    'Ended' => $s->actual_end_time?->format('Y-m-d H:i'),
                    'Hours Worked' => round($s->start_time->diffInMinutes($s->periodEnd()) / 60, 2),
                    'Extended Hours' => round($s->extended_minutes / 60, 2),
                    'Status' => $s->status,
                    'How It Ended' => $s->status === 'ended' ? $s->endReasonLabel() : '',
                    'Bookings' => $t['bookings'],
                    'Collected TZS' => $money($t['collected'], 'TZS'),
                    'Collected USD' => $money($t['collected'], 'USD'),
                    'Cash TZS' => $money($t['cash'], 'TZS'),
                    'Cash USD' => $money($t['cash'], 'USD'),
                ]);
            }
        });
    }

    private function filtered(array $filters): Builder
    {
        return Shift::query()
            ->when($filters['user_id'] ?? null, fn ($q, $id) => $q->where('user_id', $id))
            ->when($filters['from'] ?? null, fn ($q, $d) => $q->whereDate('start_time', '>=', $d))
            ->when($filters['to'] ?? null, fn ($q, $d) => $q->whereDate('start_time', '<=', $d))
            ->when($filters['status'] ?? null, fn ($q, $st) => $q->where('status', $st))
            ->orderByDesc('start_time');
    }

    private function settings(): array
    {
        return [
            'enabled' => $this->shifts->isShiftManagementEnabled(),
            'hours' => $this->shifts->getDefaultShiftHours(),
            'rest' => $this->shifts->restHours(),
            'warning' => $this->shifts->warningMinutes(),
        ];
    }
}
