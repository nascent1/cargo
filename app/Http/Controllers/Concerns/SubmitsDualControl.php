<?php

namespace App\Http\Controllers\Concerns;

use App\Services\DualControl\DualControlException;
use App\Services\DualControl\DualControlRegistry;
use App\Services\DualControlService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Submit a change through dual control and answer the browser the same way everywhere:
 * applied / scheduled / awaiting approval, as a flash message or JSON (202 when not applied yet).
 *
 * Forms add <x-dual-control.takes-effect op="..."/> to let the maker choose when it takes effect.
 */
trait SubmitsDualControl
{
    /**
     * The moment chosen in the form's "Takes effect" field, or null for "Immediately".
     */
    protected function takesEffect(Request $request, string $operation): ?Carbon
    {
        if (!DualControlRegistry::canSchedule($operation) || $request->input('takes_effect') !== 'at') {
            return null;
        }

        $request->validate(
            ['takes_effect_at' => 'required|date|after:now'],
            [
                'takes_effect_at.required' => 'Choose when the change takes effect, or pick "Immediately".',
                'takes_effect_at.after' => 'The start must be in the future, or pick "Immediately".',
            ]
        );

        return Carbon::parse($request->input('takes_effect_at'));
    }

    /**
     * @param string|RedirectResponse|null $redirect where to go afterwards (default: back)
     * @param array $options passed to DualControlService::submit (sealed, subject_key, commence_at)
     */
    protected function submitChange(
        Request $request, string $operation, string $resourceType, int $resourceId, array $payload,
        string $appliedMessage, string|RedirectResponse|null $redirect = null, array $options = []
    ) {
        $dualControl = app(DualControlService::class);

        try {
            $dcRequest = $dualControl->submit($operation, $resourceType, $resourceId, $payload, $options + [
                'commence_at' => $this->takesEffect($request, $operation),
            ]);
        } catch (DualControlException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 409);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $message = $dualControl->outcomeMessage($dcRequest, $appliedMessage);

        if ($request->expectsJson()) {
            return response()->json([
                'status' => $dcRequest->status,
                'applied' => $dcRequest->status === 'approved',
                'request_id' => $dcRequest->id,
                'resource_id' => $dcRequest->resource_id,
                'takes_effect' => $dcRequest->commence_at?->toIso8601String(),
                'message' => $message,
            ], $dcRequest->status === 'approved' ? 200 : 202);
        }

        $to = $redirect instanceof RedirectResponse ? $redirect : ($redirect ? redirect($redirect) : back());

        return $to->with('success', $message);
    }
}
