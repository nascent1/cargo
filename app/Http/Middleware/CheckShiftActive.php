<?php

namespace App\Http\Middleware;

use App\Services\ShiftService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the sales portal's booking, payment and cancel endpoints. A sales officer's shift
 * normally started when they signed in (StartShiftOnSignIn); with none running, the first action
 * starts one. Once it has ended they get 403 SHIFT_ENDED until the rest period passes or an
 * extension is approved. Viewing, printing and tracking are not guarded.
 */
class CheckShiftActive
{
    public function __construct(protected ShiftService $shiftService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$this->shiftService->appliesTo($user)) {
            return $next($request);
        }

        if (!$this->shiftService->shiftForAction($user)) {
            return response()->json([
                'message' => 'Your shift has ended. Request an extension if you need to keep selling.',
                'code' => 'SHIFT_ENDED',
                'shift' => $this->shiftService->context($user),
            ], 403);
        }

        return $next($request);
    }
}
