<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(\App\Services\StarRatingService::class);
        $this->app->singleton(\App\Services\DualControlService::class);
        $this->app->singleton(\App\Services\AuditTrailService::class);
        $this->app->singleton(\App\Services\InventoryService::class);
    }

    public function boot(): void
    {
        // Register booking lifecycle event listeners
        $listener = \App\Listeners\SendBookingNotification::class;
        Event::listen(\App\Events\BookingConfirmed::class, [$listener, 'handleBookingConfirmed']);
        Event::listen(\App\Events\BookingCancelled::class, [$listener, 'handleBookingCancelled']);
        Event::listen(\App\Events\TicketRescheduled::class, [$listener, 'handleTicketRescheduled']);
        Event::listen(\App\Events\BookingExpired::class, [$listener, 'handleBookingExpired']);

        // Hook custom permissions into Laravel's Gate
        Gate::before(function ($user, $ability) {
            if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
                return true;
            }
            if (method_exists($user, 'hasPermission') && $user->hasPermission($ability)) {
                return true;
            }
            return null;
        });
    }
}
