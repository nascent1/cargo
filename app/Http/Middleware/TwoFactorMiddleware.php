<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\SystemConfig;

class TwoFactorMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 1. Is 2FA globally enabled in the system?
        $global2faEnabled = SystemConfig::where('key_name', 'enable_2fa')->value('value') === '1';

        // 2. If globally enabled, and the user has 2FA enabled
        if ($global2faEnabled && $user && $user->mfa_enabled) {
            
            // Check if the user has completed the challenge in this session
            if (!$request->session()->has('2fa_verified')) {
                // Ignore the challenge routes to prevent redirect loop
                if (!$request->is('admin/2fa-challenge') && !$request->is('admin/2fa-verify') && !$request->is('logout')) {
                    return redirect()->guest(route('admin.2fa.challenge'));
                }
            }
        }

        return $next($request);
    }
}
