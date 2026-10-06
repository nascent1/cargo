<?php

namespace App\Http\Controllers;

use App\Models\DualControlRequest;
use App\Models\AuditLog;
use App\Services\DualControlService;
use Illuminate\Http\Request;

class DualControlController extends Controller
{
    public function index(Request $request)
    {
        $requests = DualControlRequest::with(['maker', 'checker'])
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('dual-control.index', compact('requests'));
    }

    public function show(DualControlRequest $dualControlRequest)
    {
        $dualControlRequest->load(['maker', 'checker']);
        return view('dual-control.show', compact('dualControlRequest'));
    }

    public function approve(Request $request, DualControlRequest $dualControlRequest, DualControlService $service)
    {
        $validated = $request->validate(['comment' => 'nullable|string|max:500']);

        $service->approve($dualControlRequest, $validated['comment'] ?? null);

        return redirect()->route('dual-control.index')->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, DualControlRequest $dualControlRequest, DualControlService $service)
    {
        $validated = $request->validate(['comment' => 'required|string|max:500']);

        $service->reject($dualControlRequest, $validated['comment']);

        return redirect()->route('dual-control.index')->with('success', 'Request rejected.');
    }
}
