<?php

namespace App\Http\Middleware;

use App\Services\ShiftService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * On the sales portal page, after 2FA: the first page after a sales officer signs in starts their
 * shift (or carries on the running one). Only a sign-in does this — reloading the page or the
 * portal's shift polling never starts a shift. See MarkShiftSignIn.
 */
class StartShiftOnSignIn
{
    /** Session flag set at sign-in, used up here. */
    public const PENDING = 'shift_sign_in_pending';

    public function __construct(private ShiftService $shifts)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user() && $request->hasSession() && $request->session()->pull(self::PENDING)) {
            try {
                $this->shifts->startOnSignIn($request->user());
            } catch (\Throwable $e) {
                // Never keep the clerk out of the portal; their first booking starts the shift instead
                report($e);
            }
        }

        return $next($request);
    }
}
