<?php

namespace App\Services\DualControl\Handlers;

use App\Models\DualControlRequest;
use App\Models\NotificationTemplate;
use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlHandler;
use App\Services\DualControl\StaleChangeGuard;
use Carbon\CarbonInterface;
use Illuminate\Support\Arr;

/**
 * SMS / email notification templates.
 */
class NotificationContentHandler implements DualControlHandler
{
    public function execute(DualControlRequest $request, CarbonInterface $effectiveAt): void
    {
        $after = $request->payload_after ?? [];

        if ($request->operation_type === 'notification_template_toggle') {
            $template = NotificationTemplate::find($request->resource_id) ?? throw new DualControlException('The template no longer exists.');
            StaleChangeGuard::assertFresh($request->payload_before, $after, ['is_active' => $template->is_active]);
            $template->update(['is_active' => (bool) $after['is_active']]);

            return;
        }

        $key = Arr::only($after, ['event', 'channel', 'locale']);
        $existing = NotificationTemplate::where($key)->first();
        if ($existing && !$request->payload_before) {
            throw new DualControlException('This template was saved by someone else after the request was submitted. Reject it and submit again.');
        }
        if ($existing) {
            StaleChangeGuard::assertFresh($request->payload_before, Arr::only($after, ['subject', 'body', 'is_active']),
                $existing->only(['subject', 'body', 'is_active']));
        }

        $template = NotificationTemplate::updateOrCreate($key, Arr::only($after, ['subject', 'body', 'is_active']));
        $request->update(['resource_id' => $template->id]);
    }
}
