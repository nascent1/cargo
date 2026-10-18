<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DualControlRequest;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlRegistry;
use App\Services\DualControlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DualControlController extends Controller
{
    protected DualControlService $dcService;

    public function __construct(DualControlService $dcService)
    {
        $this->dcService = $dcService;
    }

    /**
     * Tabs: inbox (awaiting someone else's approval), mine, scheduled, all — plus module / status / search filters.
     */
    public function index(Request $request)
    {
        $tab = in_array($request->tab, ['inbox', 'mine', 'scheduled', 'all'], true) ? $request->tab : 'inbox';
        $query = DualControlRequest::with(['maker', 'checker']);

        match ($tab) {
            'inbox' => $query->where('status', 'pending')->where('maker_id', '!=', Auth::id()),
            'mine' => $query->where('maker_id', Auth::id()),
            'scheduled' => $query->where('status', 'scheduled'),
            'all' => null,
        };

        if ($request->filled('status') && $tab !== 'scheduled') {
            $query->where('status', $request->status);
        }
        if ($request->filled('module')) {
            $ops = collect(DualControlRegistry::all())->where('module', $request->module)->keys();
            $query->whereIn('operation_type', $ops);
        }
        if ($request->filled('search')) {
            $term = trim($request->search);
            $query->where(function ($q) use ($term) {
                $q->where('operation_type', 'ilike', '%' . str_replace(' ', '_', $term) . '%')
                    ->orWhere('resource_type', 'ilike', "%{$term}%");
                if (ctype_digit(ltrim($term, '#DCdc-'))) {
                    $q->orWhere('id', (int) ltrim($term, '#DCdc-'));
                }
            });
        }

        $requests = $query->orderBy($tab === 'scheduled' ? 'commence_at' : 'created_at', $tab === 'scheduled' ? 'asc' : 'desc')
            ->paginate(20)
            ->appends($request->query());

        $counts = [
            'inbox' => DualControlRequest::where('status', 'pending')->where('maker_id', '!=', Auth::id())->where('expires_at', '>', now())->count(),
            'mine' => DualControlRequest::where('maker_id', Auth::id())->open()->count(),
            'scheduled' => DualControlRequest::where('status', 'scheduled')->count(),
        ];
        $modules = collect(DualControlRegistry::all())->where('legacy', false)->pluck('module')->unique()->sort()->values();

        return view('admin.dual_control.index', compact('requests', 'tab', 'counts', 'modules'));
    }

    public function show(DualControlRequest $dualControlRequest)
    {
        $dualControlRequest->load(['maker', 'checker']);

        return view('admin.dual_control.show', ['dualControlRequest' => $dualControlRequest]);
    }

    public function approve(Request $request, DualControlRequest $dualControlRequest)
    {
        try {
            $result = $this->dcService->approve($dualControlRequest, $request->input('comment'));
        } catch (DualControlException $e) {
            return back()->with('error', 'Not approved: ' . $e->getMessage());
        }

        $message = $result->status === 'scheduled'
            ? 'Approved — the change takes effect ' . $result->commence_at->format('d M Y H:i') . '.'
            : 'Approved and applied' . ($result->commencedLate() ? ' (its requested start had passed, so it applied now).' : '.');

        return redirect()->route('admin.dual-control.index')->with('success', $message);
    }

    public function reject(Request $request, DualControlRequest $dualControlRequest)
    {
        $request->validate(['comment' => 'required|string|max:500']);
        $this->dcService->reject($dualControlRequest, $request->input('comment'));

        return redirect()->route('admin.dual-control.index')->with('success', 'Request rejected.');
    }

    public function cancel(Request $request, DualControlRequest $dualControlRequest)
    {
        $request->validate(['reason' => 'nullable|string|max:500']);
        $this->dcService->cancel($dualControlRequest, $request->input('reason'));

        return redirect()->route('admin.dual-control.show', $dualControlRequest)->with('success', "Request #DC-{$dualControlRequest->id} cancelled.");
    }
}
