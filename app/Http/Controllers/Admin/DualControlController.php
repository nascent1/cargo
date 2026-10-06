<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DualControlRequest;
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

    public function index(Request $request)
    {
        $query = DualControlRequest::with(['maker', 'checker']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('operation_type', 'like', "%{$search}%")
                  ->orWhere('target_model', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        } else {
            $query->where('status', 'pending');
        }

        $requests = $query->orderBy('created_at', 'desc')
            ->paginate(15)
            ->appends($request->query());
            
        return view('admin.dual_control.index', compact('requests'));
    }

    public function show(DualControlRequest $dualControlRequest)
    {
        return view('admin.dual_control.show', compact('dualControlRequest'));
    }

    public function approve(Request $request, DualControlRequest $dualControlRequest)
    {
        $this->dcService->approve($dualControlRequest, $request->input('comment'));
        return redirect()->route('admin.dual-control.index')->with('success', 'Request approved successfully.');
    }

    public function reject(Request $request, DualControlRequest $dualControlRequest)
    {
        $request->validate(['comment' => 'required|string|max:500']);
        $this->dcService->reject($dualControlRequest, $request->input('comment'));
        return redirect()->route('admin.dual-control.index')->with('success', 'Request rejected.');
    }
}
