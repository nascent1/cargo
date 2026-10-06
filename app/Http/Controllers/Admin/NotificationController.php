<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationTemplate;
use App\Models\NotificationLog;
use App\Models\Announcement;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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

        NotificationTemplate::updateOrCreate(
            ['event' => $validated['event'], 'channel' => $validated['channel'], 'locale' => $validated['locale']],
            ['subject' => $validated['subject'] ?? null, 'body' => $validated['body'], 'is_active' => true]
        );

        return redirect()->route('admin.notifications.templates')->with('success', 'Template saved.');
    }

    public function toggleTemplate(NotificationTemplate $template)
    {
        $template->update(['is_active' => !$template->is_active]);
        return redirect()->back()->with('success', 'Template toggled.');
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

    // ── Announcements ───────────────────────────────────────

    public function announcements(Request $request)
    {
        $announcements = Announcement::when($request->type, fn($q, $t) => $q->where('type', $t))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.notifications.announcements', compact('announcements'));
    }

    public function storeAnnouncement(Request $request)
    {
        $validated = $request->validate([
            'title'           => 'required|string|max:255',
            'message'         => 'required|string',
            'type'            => 'required|in:info,warning,emergency,maintenance',
            'target_audience' => 'required|in:all,agents,passengers,operators',
            'route_id'        => 'nullable|exists:routes,id',
            'start_date'      => 'required|date',
            'end_date'        => 'nullable|date|after_or_equal:start_date',
        ]);

        Announcement::create(array_merge($validated, [
            'is_active'  => true,
            'created_by' => auth()->id(),
        ]));

        return redirect()->route('admin.notifications.announcements')->with('success', 'Announcement created.');
    }

    public function toggleAnnouncement(Announcement $announcement)
    {
        $announcement->update(['is_active' => !$announcement->is_active]);
        return redirect()->back()->with('success', 'Announcement toggled.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $announcement->delete();
        return redirect()->route('admin.notifications.announcements')->with('success', 'Announcement deleted.');
    }
}
