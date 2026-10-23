<?php

namespace App\Http\Middleware;

use App\Services\ExchangeRateService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Session;

/**
 * Whoever keeps the exchange rate up to date (exchange_rate:maintain) must set the USD→TZS rate when it is due:
 * every exchange_rate_update_frequency days (0 = never). Pages still open, with the rate prompt on top;
 * changes are held back until the rate is set.
 */
class ForceExchangeRateUpdate
{
    // Open while the rate is due: settings (e.g. the frequency), dual control (a checker can approve a pending rate)
    // and the prompt's own save
    private const EXEMPT = ['admin/settings*', 'logout', 'portal*', 'admin/password*', 'admin/dual-control*', 'admin/exchange-rate/*'];

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->check() || $request->is(...self::EXEMPT) || !auth()->user()->hasPermission('exchange_rate:maintain')
            || !ExchangeRateService::updateStatus()['due']) {
            return $next($request);
        }

        if ($request->isMethod('GET')) {
            if (!$request->expectsJson()) {
                Session::now('force_exchange_modal', true);
            }

            return $next($request);
        }

        $message = "Set today's USD to TZS exchange rate first.";
        if ($request->expectsJson()) {
            // The page's background request: it opens the rate prompt instead of failing silently
            return response()->json(['message' => $message, 'exchange_rate_due' => true], 423);
        }

        return redirect()->back()->with('force_exchange_modal', true)->with('error', $message);
    }
}
