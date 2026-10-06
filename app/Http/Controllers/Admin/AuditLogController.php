<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::with('user')
            ->when($request->action, fn($q, $a) => $q->where('action', $a))
            ->when($request->entity_type, fn($q, $e) => $q->where('entity_type', $e))
            ->when($request->user_id, fn($q, $u) => $q->where('user_id', $u))
            ->when($request->from, fn($q, $f) => $q->where('created_at', '>=', $f))
            ->when($request->to, fn($q, $t) => $q->where('created_at', '<=', $t))
            ->orderByDesc('sequence_number')
            ->paginate(20)
            ->withQueryString();

        $entityTypes = AuditLog::distinct()->pluck('entity_type');
        $actions = AuditLog::distinct()->pluck('action');

        return view('admin.audit.index', compact('logs', 'entityTypes', 'actions'));
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user');
        return view('admin.audit.show', compact('auditLog'));
    }

    public function verifyChain(AuditTrailService $service)
    {
        $result = $service->verifyChainIntegrity();

        return redirect()->route('admin.audit.index')
            ->with($result['valid'] ? 'success' : 'error',
                $result['valid']
                    ? "Chain integrity verified. {$result['checked']} entries checked."
                    : "Chain integrity FAILED. {$result['checked']} entries checked, " . count($result['errors']) . " errors found."
            );
    }
}
