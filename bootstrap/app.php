<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        
        $middleware->alias([
            'role'          => \App\Http\Middleware\CheckRole::class,
            'permission'    => \App\Http\Middleware\CheckPermission::class,
            'check_shift' => \App\Http\Middleware\CheckShiftActive::class,
            'portal.access' => \App\Http\Middleware\PortalAccessMiddleware::class,
            'admin.access'  => \App\Http\Middleware\AdminAccessMiddleware::class,
            'idempotent'    => \App\Http\Middleware\IdempotencyMiddleware::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\AccessLogMiddleware::class,
            \App\Http\Middleware\AccountLockoutMiddleware::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);

        $middleware->statefulApi();

        $middleware->api(append: [
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\ForcePasswordChange::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
