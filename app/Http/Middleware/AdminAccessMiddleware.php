<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAccessMiddleware
{
    /**
     * Handle an incoming request for the Admin Panel.
     * If the authenticated user is an agent, associate agent, or counter/POS staff without admin privileges, redirect them to /portal.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        // Allow access to password change routes for everyone caught by the force password middleware
        if ($request->is('admin/password*')) {
            return $next($request);
        }

        // 1. If the user is an agent or associate agent, immediately bounce them to /portal
        if (in_array($user->domain, ['agent', 'associate_agent'])) {
            return redirect('/portal');
        }

        // 2. If the user is a system_user but purely ticketing/sales counter staff, bounce them to /portal
        if ($user->domain === 'system_user') {
            if (!$user->hasPermission('admin_portal:access')) {
                return redirect('/portal');
            }
        }

        return $next($request);
    }
}
