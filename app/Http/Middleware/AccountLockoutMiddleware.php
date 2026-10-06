<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AccountLockoutMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->isLocked()) {
            auth()->logout();
            $request->session()->invalidate();

            return $request->expectsJson()
                ? response()->json(['message' => 'Account is locked. Please try again later.'], 423)
                : redirect()->route('login')->withErrors(['email' => 'Your account is temporarily locked. Please try again later.']);
        }

        if ($user && in_array($user->status, ['suspended', 'inactive'])) {
            auth()->logout();
            $request->session()->invalidate();

            return $request->expectsJson()
                ? response()->json(['message' => 'Account is ' . $user->status . '.'], 403)
                : redirect()->route('login')->withErrors(['email' => 'Your account has been ' . $user->status . '. Please contact support.']);
        }

        return $next($request);
    }
}
