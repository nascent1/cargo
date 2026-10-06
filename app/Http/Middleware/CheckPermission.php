<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return $request->expectsJson()
                ? response()->json(['message' => 'Unauthenticated.'], 401)
                : redirect()->route('login');
        }


        if (!empty($permissions)) {
            $expandedPermissions = [];
            foreach ($permissions as $p) {
                foreach (preg_split('/[,|]/', $p) as $part) {
                    if (!empty(trim($part))) {
                        $expandedPermissions[] = trim($part);
                    }
                }
            }

            if (!$user->hasAnyPermission($expandedPermissions)) {
                if ($request->expectsJson()) {
                    return response()->json(['message' => 'Permission denied.'], 403);
                }
                abort(403, 'Permission denied. You do not have the required access.');
            }
        }

        return $next($request);
    }
}
