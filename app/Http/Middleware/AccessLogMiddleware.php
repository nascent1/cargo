<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\AccessLog;
use Symfony\Component\HttpFoundation\Response;

class AccessLogMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Log page views/accesses for authenticated users
        if (auth()->check()) {
            // Avoid logging AJAX, JSON, or debug routes to prevent log bloat
            if ($request->isMethod('GET') && !$request->ajax() && !$request->wantsJson()) {
                $route = $request->route();
                $routeName = $route ? $route->getName() : null;

                AccessLog::log('access', auth()->id(), [
                    'url' => $request->getRequestUri(),
                    'route' => $routeName,
                    'method' => $request->method(),
                ]);
            }
        }

        return $response;
    }
}
