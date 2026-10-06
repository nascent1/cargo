<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PortalAccessMiddleware
{
    /**
     * Handle an incoming request for the Sales & Agent Portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }

        if ($user->isLocked() || $user->status !== 'active') {
            return $request->expectsJson()
                ? response()->json(['message' => 'Your account is inactive or locked.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Your account is inactive or locked.']);
        }

        // 1. Allow full travel agents and associate agents
        if (in_array($user->domain, ['agent', 'associate_agent'])) {
            return $next($request);
        }

        // 2. Allow system users (sales officers, supervisors, counter staff, and senior admins)
        if ($user->domain === 'system_user') {
            if ($user->hasPermission('portal:access')) {
                return $next($request);
            }
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized. You do not have access to the Sales & Agent Portal.'], 403);
        }

        abort(403, 'Unauthorized. You do not have access to the Sales & Agent Portal.');
    }
}
