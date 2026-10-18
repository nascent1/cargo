<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\BusinessDayClosure;
use App\Services\Cargo\CloseOfBusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Close-of-business panel on Settings › Integrations: preview, test send, resend.
 */
class BusinessDayClosureController extends Controller
{
    public function __construct(private CloseOfBusinessService $service)
    {
    }

    public function preview(): JsonResponse
    {
        return response()->json($this->service->preview());
    }

    public function sendToMe(Request $request): RedirectResponse
    {
        $log = $this->service->sendPreviewTo($request->user());
        AuditLog::createEntry($request->user()->id, 'close_of_business_test', 'business_day_closure', null, null, ['status' => $log->status], $request->ip());

        return $log->status === 'sent'
            ? back()->with('success', 'Test close-of-business SMS sent to your phone.')
            : back()->with('error', 'Test SMS not sent: ' . ($log->error_message ?? 'unknown error'));
    }

    public function resend(Request $request, BusinessDayClosure $closure): RedirectResponse
    {
        $closure = $this->service->deliver($closure);
        AuditLog::createEntry($request->user()->id, 'close_of_business_resend', 'business_day_closure', $closure->id, null,
            ['sent' => $closure->sent_count, 'recipients' => $closure->recipients_count], $request->ip());

        return back()->with('success', "Close-of-business SMS for {$closure->business_date->format('d M Y')}: {$closure->sent_count} of {$closure->recipients_count} delivered.");
    }
}
