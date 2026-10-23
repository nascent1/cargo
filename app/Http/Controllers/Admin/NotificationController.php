<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\SubmitsDualControl;
use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use SubmitsDualControl;

    // ── Templates ───────────────────────────────────────────

    public function templates(Request $request)
    {
        $templates = NotificationTemplate::when($request->event, fn($q, $e) => $q->where('event', $e))
            ->when($request->channel, fn($q, $c) => $q->where('channel', $c))
            ->orderBy('event')
            ->orderBy('channel')
            ->paginate(30)
            ->withQueryString();

        $events = NotificationTemplate::select('event')->distinct()->pluck('event');
        $channels = ['sms', 'email', 'in_app', 'push'];

        return view('admin.notifications.templates', compact('templates', 'events', 'channels'));
    }

    public function storeTemplate(Request $request)
    {
        $validated = $request->validate([
            'event'   => 'required|string|max:100',
            'channel' => 'required|in:sms,email,in_app,push',
            'locale'  => 'required|string|max:5',
            'subject' => 'nullable|string|max:255',
            'body'    => 'required|string',
        ]);

        // Through dual control (notification_template_save): adds the template or replaces the event + channel + locale one
        $existing = NotificationTemplate::where('event', $validated['event'])->where('channel', $validated['channel'])->where('locale', $validated['locale'])->first();

        return $this->submitChange($request, 'notification_template_save', 'notification_template', $existing?->id ?? 0, [
            'before' => $existing?->only(['event', 'channel', 'locale', 'subject', 'body', 'is_active']),
            'after' => ['subject' => $validated['subject'] ?? null, 'body' => $validated['body'], 'is_active' => true] + $validated,
        ], 'Template saved.', route('admin.notifications.templates'), [
            'subject_key' => "notification_template:{$validated['event']}:{$validated['channel']}:{$validated['locale']}",
        ]);
    }

    public function toggleTemplate(Request $request, NotificationTemplate $template)
    {
        return $this->submitChange($request, 'notification_template_toggle', 'notification_template', $template->id, [
            'before' => ['is_active' => $template->is_active],
            'after' => ['is_active' => !$template->is_active],
        ], $template->is_active ? 'Template switched off.' : 'Template switched on.', null, [
            'subject_key' => "notification_template:{$template->event}:{$template->channel}:{$template->locale}",
        ]);
    }

    // ── Delivery Logs ───────────────────────────────────────

    public function logs(Request $request)
    {
        $logs = NotificationLog::with('user')
            ->when($request->channel, fn($q, $c) => $q->where('channel', $c))
            ->when($request->status, fn($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.notifications.logs', compact('logs'));
    }
}
