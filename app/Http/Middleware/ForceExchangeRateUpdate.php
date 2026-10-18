<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

class ForceExchangeRateUpdate
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->hasPermission('exchange_rate:bypass_force_update')) {
            // Check if we are already on a settings route or logging out to avoid redirect loops
            // Dual control stays open so a checker can approve a pending rate (otherwise nobody could unblock it)
            if (!$request->is('admin/settings*') && !$request->is('logout') && !$request->is('portal*') && !$request->is('admin/password*') && !$request->is('admin/dual-control*')) {
                
                $lastUpdatedStr = \App\Models\SystemConfig::getValue('exchange_rate_last_updated', '2000-01-01');
                
                try {
                    $lastUpdated = \Carbon\Carbon::parse($lastUpdatedStr)->startOfDay();
                    $today = today();
                    
                    if ($lastUpdated->lt($today)) {
                        if ($request->isMethod('GET') && !$request->ajax()) {
                            Session::now('force_exchange_modal', true);
                            return $next($request);
                        }
                        return redirect()->back()->with('force_exchange_modal', true)->with('error', 'You must update the daily exchange rate before proceeding.');
                    }
                } catch (\Exception $e) {
                    if ($request->isMethod('GET') && !$request->ajax()) {
                        Session::now('force_exchange_modal', true);
                        return $next($request);
                    }
                    return redirect()->back()->with('force_exchange_modal', true)->with('error', 'Please configure the daily exchange rate.');
                }
            }
        }

        return $next($request);
    }
}
