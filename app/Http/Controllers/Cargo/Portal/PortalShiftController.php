<?php

namespace App\Http\Controllers\Cargo\Portal;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Shift;
use App\Services\DualControl\DualControlException;
use App\Services\DualControlService;
use App\Services\ShiftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The clerk's own shift in the sales portal: status and figures, ending it (cash handover
 * summary), and asking for more time (shift_extension — always waits for a checker, even when
 * the rule is off; the rule only covers extensions supervisors start in Admin › Shifts).
 */
class PortalShiftController extends Controller
{
    public function __construct(private ShiftService $shifts)
    {
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->shifts->context($request->user()));
    }

    public function end(Request $request): JsonResponse
    {
        $user = $request->user();
        $shift = $this->shifts->getActiveShift($user);
        if (!$shift || !$shift->isActive()) {
            return response()->json(['message' => 'You are not on shift.'], 422);
        }

        $this->shifts->endShift($shift, Shift::END_SELF, $user->id);
        AuditLog::createEntry($user->id, 'shift_end', 'shift', $shift->id, null, ['end_reason' => Shift::END_SELF], $request->ip());

        return response()->json([
            'message' => 'Shift ended. Hand over the cash shown on your shift summary.',
            'shift' => $this->shifts->context($user),
        ]);
    }

    public function requestExtension(Request $request, DualControlService $dualControl): JsonResponse
    {
        $validated = $request->validate([
            'hours' => 'required|numeric|min:0.5|max:12',
            'reason' => 'required|string|max:255',
        ]);
        $user = $request->user();

        if (!$this->shifts->appliesTo($user)) {
            return response()->json(['message' => 'Your account does not work in shifts.'], 422);
        }
        $shift = $this->shifts->getActiveShift($user) ?? $this->shifts->latestShift($user);
        if (!$shift) {
            return response()->json(['message' => 'You are not on shift yet, so there is nothing to extend.'], 422);
        }
        if ($shift->status === 'ended' && !$this->shifts->isResting($user)) {
            return response()->json(['message' => 'You can already start a new shift: it starts when you sign in again or make your next booking.'], 422);
        }

        $hours = (float) $validated['hours'];
        try {
            $dcRequest = $dualControl->submit('shift_extension', 'shift', $shift->id, [
                'before' => [
                    'clerk' => $user->full_name,
                    'started_at' => $shift->start_time->format('d M Y H:i'),
                    'ends_at' => $shift->end_time?->format('d M Y H:i'),
                    'status' => $shift->isActive() ? 'on shift' : 'ended',
                    'extended_minutes' => (int) $shift->extended_minutes,
                ],
                'after' => ['hours' => $hours, 'reason' => $validated['reason']],
            ], ['subject_key' => $shift->subjectKey(), 'require_approval' => true]);
        } catch (DualControlException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'message' => $dualControl->outcomeMessage($dcRequest, 'Your shift was extended by ' . rtrim(rtrim(number_format($hours, 1), '0'), '.') . ' h.'),
            'shift' => $this->shifts->context($user),
        ]);
    }
}
