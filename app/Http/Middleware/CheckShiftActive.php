<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\ShiftService;

class CheckShiftActive
{
    protected ShiftService $shiftService;

    public function __construct(ShiftService $shiftService)
    {
        $this->shiftService = $shiftService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Only enforce for sales officers
        if (!$user || $user->domain !== 'system_user') {
            return $next($request);
        }

        if (!$this->shiftService->isShiftManagementEnabled()) {
            return $next($request);
        }

        $shift = $this->shiftService->getActiveShift($user);

        if (!$shift) {
            // First action, start shift
            $this->shiftService->startShift($user);
            return $next($request);
        }

        // Check if shift is expired
        if ($shift->end_time && now()->isAfter($shift->end_time)) {
            $this->shiftService->endShift($shift);

            // Log them out or return 403
            // Since this is an API route (usually called from SPA), returning 403 with specific code is best.
            // The frontend will intercept this and redirect to a "Shift Ended" screen.
            return response()->json([
                'message' => 'Your shift has ended.',
                'code' => 'SHIFT_ENDED'
            ], 403);
        }

        return $next($request);
    }
}
