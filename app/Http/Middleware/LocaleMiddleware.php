<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $supportedLocales = \App\Models\Language::where('is_active', true)->pluck('code')->toArray();
        if (empty($supportedLocales)) {
            $supportedLocales = ['en'];
        }

        // Priority: query param > user preference > Accept-Language > default
        $locale = $request->query('lang')
            ?? $request->user()?->locale_pref
            ?? $request->getPreferredLanguage($supportedLocales)
            ?? config('app.locale', 'en');

        if (in_array($locale, $supportedLocales)) {
            app()->setLocale($locale);
        }

        return $next($request);
    }
}
