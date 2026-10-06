<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $user = auth()->user();

            // Only force system users, agents, and associates
            if (in_array($user->domain, ['system_user', 'agent', 'associate_agent', 'associate'])) {
                
                // Allow them to visit the force change screen or logout
                if ($request->is('admin/password/force-change') || $request->is('admin/password/update') || $request->is('logout')) {
                    return $next($request);
                }

                $needsChange = false;

                if (is_null($user->password_changed_at)) {
                    $needsChange = true;
                } else {
                    $expiryDays = (int) \App\Models\SystemConfig::getValue('password_expiry_days', 30);
                    if ($user->password_changed_at->copy()->addDays($expiryDays)->isPast()) {
                        $needsChange = true;
                    }
                }

                if ($needsChange) {
                    if ($request->wantsJson() || $request->ajax()) {
                        return response()->json(['message' => 'Password change required', 'force_password_change' => true], 403);
                    }
                    return redirect()->route('admin.password.force-change')->with('error', 'For security reasons, you must change your password before proceeding.');
                }
            }
        }

        return $next($request);
    }
}
