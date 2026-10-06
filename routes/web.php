<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\SystemConfigController;
use App\Http\Controllers\Admin\LookupController;
use App\Http\Controllers\Admin\ShiftController;
use App\Http\Controllers\Admin\DualControlController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\FerryController;
use App\Http\Controllers\Admin\PortController;
use App\Http\Controllers\Admin\RouteController;
use App\Http\Controllers\Admin\ScheduleController;
use App\Http\Controllers\Admin\TwoFactorController;

/*
|--------------------------------------------------------------------------
| UCLP Cargo Platform Web Routes
|--------------------------------------------------------------------------
*/

// ── Public ─────────────────────────────────────────────────────────
Route::get('/', function () {
    if (auth()->check()) {
        $user = auth()->user();
        if ($user->can('admin_portal:access')) {
            return redirect()->route('admin.dashboard');
        }
        if ($user->can('portal:access')) {
            return redirect('/portal');
        }
        return redirect()->route('admin.dashboard');
    }
    return redirect()->route('login');
});

// Dummy notifications route to prevent 500 error on admin layout
Route::get('/api/notifications', fn() => response()->json([]))->name('api.notifications');

// ── Authentication ─────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('login', [AuthController::class, 'login'])->name('login.submit');

    // Password Reset Routes
    Route::get('forgot-password', [\App\Http\Controllers\Admin\PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('forgot-password', [\App\Http\Controllers\Admin\PasswordResetController::class, 'sendResetLinkEmail'])->name('password.email');
    Route::get('reset-password/{token}', [\App\Http\Controllers\Admin\PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('reset-password', [\App\Http\Controllers\Admin\PasswordResetController::class, 'reset'])->name('password.update');
});

// ── Authenticated Routes ───────────────────────────────────────────
Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('logout');

    // Portal API: Change Password
    Route::post('/portal/api/change-password', [SystemConfigController::class, 'portalChangePassword'])
        ->middleware(['portal.access']);

    // ── Portal 2FA API Routes ──────────────────────────────────────
    Route::prefix('portal/api')->name('portal.api.')->middleware(['portal.access', \App\Http\Middleware\TwoFactorMiddleware::class])->group(function () {
        Route::get('2fa-setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('2fa-confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
        Route::post('2fa-disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::get('2fa-challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge')->withoutMiddleware([\App\Http\Middleware\TwoFactorMiddleware::class]);
        Route::post('2fa-verify', [TwoFactorController::class, 'verify'])->name('2fa.verify')->withoutMiddleware([\App\Http\Middleware\TwoFactorMiddleware::class]);
    });

    // Portal /me (returns authenticated user + roles + permissions)
    Route::get('/portal/api/me', function (\Illuminate\Http\Request $request) {
        $user = $request->user();
        $roleIds = \DB::table('user_roles')->where('user_id', $user->id)->pluck('role_id');
        $roles = \DB::table('roles')->whereIn('id', $roleIds)->pluck('slug')->toArray();
        $permissions = \DB::table('role_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_permissions.permission_id')
            ->whereIn('role_permissions.role_id', $roleIds)
            ->pluck('permissions.name')
            ->unique()
            ->values()
            ->toArray();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'domain' => $user->domain,
            ],
            'roles' => $roles,
            'permissions' => $permissions,
            'wallet' => [
                'balance' => $user->wallet_balance ?? 0,
                'currency' => 'TZS',
            ],
        ]);
    })->middleware('portal.access');

    // Portal cargo API endpoints (web session auth)
    Route::prefix('portal/api/cargo')->middleware('portal.access')->group(function () {
        $ctrl = \App\Http\Controllers\Cargo\Portal\PortalCargoController::class;
        Route::get('types',           [$ctrl, 'getCargoTypes']);
        Route::get('payment-methods', [\App\Http\Controllers\Cargo\CargoPaymentController::class, 'methods']);
        Route::get('availability',    [$ctrl, 'getAvailableServices']);
        Route::post('calculate-fare', [$ctrl, 'calculateFare']);
        Route::post('lock-inventory', [$ctrl, 'acquireLock']);
        Route::post('release-lock',   [$ctrl, 'releaseLock']);
        Route::post('confirm-booking',[$ctrl, 'confirmBooking']);
        Route::post('cancel/{consignment}', [$ctrl, 'cancelBooking']);
        Route::get('services',          [$ctrl, 'getAvailableServices']);
        Route::get('consignments',      [$ctrl, 'getConsignments']);
        Route::get('consignments/{consignment}', [$ctrl, 'showConsignment']);
        Route::patch('consignments/{consignment}/status', [$ctrl, 'updateStatus']);
        Route::get('consignments/{consignment}/print',    [$ctrl, 'printReceipt']);
        Route::get('dashboard-summary', [$ctrl, 'dashboardSummary']);

        // Payments (USSD push, Lipa Namba, cash) — shared with the admin portal
        $payCtrl = \App\Http\Controllers\Cargo\CargoPaymentController::class;
        Route::post('consignments/{consignment}/payments',      [$payCtrl, 'start']);
        Route::get('payments/{cargoPayment}',                   [$payCtrl, 'show']);
        Route::post('payments/{cargoPayment}/resend',           [$payCtrl, 'resend']);
        Route::post('payments/{cargoPayment}/confirm',          [$payCtrl, 'confirmManual']);
        Route::post('payments/{cargoPayment}/sms-instructions', [$payCtrl, 'smsInstructions']);
    });

    Route::view('/portal/{any?}', 'portal.spa')
        ->where('any', '.*')
        ->middleware(['portal.access', \App\Http\Middleware\TwoFactorMiddleware::class])
        ->name('portal.spa');

    // ── Admin Panel ────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware(['admin.access', \App\Http\Middleware\TwoFactorMiddleware::class, \App\Http\Middleware\ForceExchangeRateUpdate::class])->group(function () {

        // 2FA Routes
        Route::get('2fa-setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
        Route::post('2fa-confirm', [TwoFactorController::class, 'confirm'])->name('2fa.confirm');
        Route::post('2fa-disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');
        Route::get('2fa-challenge', [TwoFactorController::class, 'challenge'])
            ->name('2fa.challenge')
            ->withoutMiddleware([
                \App\Http\Middleware\TwoFactorMiddleware::class,
                \App\Http\Middleware\AdminAccessMiddleware::class
            ]);
        Route::post('2fa-verify', [TwoFactorController::class, 'verify'])
            ->name('2fa.verify')
            ->withoutMiddleware([
                \App\Http\Middleware\TwoFactorMiddleware::class,
                \App\Http\Middleware\AdminAccessMiddleware::class
            ]);

        // Force Password Change Routes
        Route::get('password/force-change', [\App\Http\Controllers\Admin\ForcePasswordController::class, 'show'])->name('password.force-change');
        Route::post('password/update', [\App\Http\Controllers\Admin\ForcePasswordController::class, 'update'])->name('password.update');

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Users CRUD
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index')->middleware('permission:user:list');
            Route::get('/create', [UserController::class, 'create'])->name('create')->middleware('permission:user:create');
            Route::post('/', [UserController::class, 'store'])->name('store')->middleware('permission:user:create');
            Route::get('/{user}', [UserController::class, 'show'])->name('show')->middleware('permission:user:read');
            Route::patch('/{user}/status', [UserController::class, 'updateStatus'])->name('update-status')->middleware('permission:user:update');
            Route::put('/{user}/roles', [UserController::class, 'updateRoles'])->name('update-roles')->middleware('permission:role:assign');
            Route::post('/{user}/reset-2fa', [UserController::class, 'reset2fa'])->name('reset-2fa')->middleware('permission:user:update');
            Route::post('/{user}/reset-password', [UserController::class, 'resetPassword'])->name('reset-password')->middleware('permission:user:update');
        });

        // Agents & Float Management
        Route::prefix('agents')->name('agents.')->group(function () {
            Route::get('/', [\App\Http\Controllers\Admin\AgentController::class, 'index'])->name('index')->middleware('permission:user:list');
            Route::post('/', [\App\Http\Controllers\Admin\AgentController::class, 'store'])->name('store')->middleware('permission:user:create');
            Route::get('/{agent}', [\App\Http\Controllers\Admin\AgentController::class, 'show'])->name('show')->middleware('permission:user:read');
            Route::put('/{agent}', [\App\Http\Controllers\Admin\AgentController::class, 'update'])->name('update')->middleware('permission:user:update');
            Route::put('/{agent}/ferries', [\App\Http\Controllers\Admin\AgentController::class, 'updateFerries'])->name('update-ferries')->middleware('permission:user:update');
            Route::put('/{agent}/routes', [\App\Http\Controllers\Admin\AgentController::class, 'updateRoutes'])->name('update-routes')->middleware('permission:user:update');
            Route::post('/{agent}/topup', [\App\Http\Controllers\Admin\AgentController::class, 'topup'])->name('topup')->middleware('permission:user:update');
            Route::post('/{agent}/credit-limit', [\App\Http\Controllers\Admin\AgentController::class, 'updateCreditLimit'])->name('update-credit-limit')->middleware('permission:user:update');
            Route::post('/{agent}/associates', [\App\Http\Controllers\Admin\AgentController::class, 'storeAssociate'])->name('store-associate')->middleware('permission:user:create');
            Route::put('/{agent}/associates/{associate}', [\App\Http\Controllers\Admin\AgentController::class, 'updateAssociate'])->name('update-associate')->middleware('permission:user:update');
            Route::delete('/{agent}/associates/{associate}', [\App\Http\Controllers\Admin\AgentController::class, 'destroyAssociate'])->name('destroy-associate')->middleware('permission:user:update');
        });

        // Audit Logs
        Route::prefix('audit')->name('audit.')->group(function () {
            Route::get('/', [AuditLogController::class, 'index'])->name('index')->middleware('permission:audit:read');
            Route::get('/verify', [AuditLogController::class, 'verifyChain'])->name('verify')->middleware('permission:audit:verify');
            Route::get('/{auditLog}', [AuditLogController::class, 'show'])->name('show')->middleware('permission:audit:read');
        });

        // System Config / Settings (Tabbed)
        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SystemConfigController::class, 'index'])->name('index')->middleware('permission:setting:read');
            Route::post('/', [SystemConfigController::class, 'update'])->name('update')->middleware('permission:setting:update');
            Route::get('/roles', [SystemConfigController::class, 'roles'])->name('roles')->middleware('permission:role:list');
            Route::post('/roles/{role}/permissions', [SystemConfigController::class, 'updateRolePermissions'])->name('roles.permissions')->middleware('permission:role:manage');
            Route::get('/permissions', [SystemConfigController::class, 'permissions'])->name('permissions')->middleware('permission:role:list');
            
            // Menus
            Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class)->except(['show']);
            Route::post('menus/order', [\App\Http\Controllers\Admin\MenuController::class, 'updateOrder'])->name('menus.update-order')->middleware('permission:setting:update');

            // Lookups & Statuses
            Route::get('/lookups', [LookupController::class, 'index'])->name('lookups.index')->middleware('permission:setting:read');
            Route::post('/lookups/{table}', [LookupController::class, 'updateOrCreate'])->name('lookups.update-or-create')->middleware('permission:setting:update');
            Route::delete('/lookups/{table}/{id}', [LookupController::class, 'destroy'])->name('lookups.destroy')->middleware('permission:setting:update');

            // Dual Control Settings
            Route::get('/dual-control-configs', [\App\Http\Controllers\Admin\DualControlConfigController::class, 'index'])->name('dual-control-configs.index')->middleware('permission:setting:read');
            Route::post('/dual-control-configs/{dualControlConfig}/toggle', [\App\Http\Controllers\Admin\DualControlConfigController::class, 'toggle'])->name('dual-control-configs.toggle')->middleware('permission:setting:update');

            // Payment Channels (checkout channels, provider credentials, payment rules)
            $channelCtrl = \App\Http\Controllers\Admin\PaymentChannelController::class;
            Route::get('/payment-channels', [$channelCtrl, 'index'])->name('payment-channels.index')->middleware('permission:setting:read');
            Route::post('/payment-channels', [$channelCtrl, 'storeMethod'])->name('payment-channels.store')->middleware('permission:setting:update');
            Route::put('/payment-channels/rules', [$channelCtrl, 'updateSettings'])->name('payment-channels.rules')->middleware('permission:setting:update');
            Route::put('/payment-channels/providers/{driver}', [$channelCtrl, 'updateProvider'])->name('payment-channels.provider')->middleware('permission:setting:update');
            Route::put('/payment-channels/{paymentMethod}', [$channelCtrl, 'updateMethod'])->name('payment-channels.update')->middleware('permission:setting:update');

            // Exchange Rates Management
            Route::get('/exchange-rates', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'index'])->name('exchange-rates.index')->middleware('permission:setting:read');
            Route::post('/exchange-rates', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'store'])->name('exchange-rates.store')->middleware('permission:setting:update');
            Route::delete('/exchange-rates/{exchangeRate}', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'destroy'])->name('exchange-rates.destroy')->middleware('permission:setting:update');

            // Change Password (available to all authenticated users)
            Route::get('/change-password', [SystemConfigController::class, 'showChangePassword'])->name('change-password');
            Route::post('/change-password', [SystemConfigController::class, 'changePassword'])->name('change-password.update');

            // Role Management
            Route::post('/roles', [SystemConfigController::class, 'createRole'])->name('roles.create')->middleware('permission:role:manage');
            Route::put('/roles/{role}', [SystemConfigController::class, 'updateRole'])->name('roles.update')->middleware('permission:role:manage');
            Route::delete('/roles/{role}', [SystemConfigController::class, 'deleteRole'])->name('roles.delete')->middleware('permission:role:manage');
        });

        // Transport Services (formerly Voyages)
        Route::get('/voyages', [\App\Http\Controllers\Admin\VoyageController::class, 'index'])->name('voyages.index')->middleware('permission:voyage:read');
        Route::get('/availability', [\App\Http\Controllers\Admin\VoyageController::class, 'availabilityIndex'])->name('availability.index')->middleware('permission:availability:read');
        Route::get('/voyages/{voyage}/availability', [\App\Http\Controllers\Admin\VoyageController::class, 'manageAvailability'])->name('voyages.availability')->middleware('permission:availability:manage');
        Route::post('/voyages/{voyage}/availability', [\App\Http\Controllers\Admin\VoyageController::class, 'updateAvailability'])->name('voyages.availability.update')->middleware('permission:availability:manage');
        Route::patch('/voyages/{voyage}/status', [\App\Http\Controllers\Admin\VoyageController::class, 'updateStatus'])->name('voyages.update-status')->middleware('permission:voyage:update');
        Route::patch('/voyages/{voyage}/weather', [\App\Http\Controllers\Admin\VoyageController::class, 'updateWeather'])->name('voyages.update-weather')->middleware('permission:voyage:update');

        // Reports
        Route::prefix('reports')->name('reports.')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/revenue', [ReportController::class, 'revenue'])->name('revenue');
            Route::get('/occupancy', [ReportController::class, 'occupancy'])->name('occupancy');
            Route::get('/float-ledger', [ReportController::class, 'floatLedger'])->name('float_ledger');
            Route::get('/agent-performance', [ReportController::class, 'agentPerformance'])->name('agent_performance');
            Route::get('/bookings', [ReportController::class, 'bookings'])->name('bookings');
            Route::get('/manifest', [ReportController::class, 'manifest'])->name('manifest');
            Route::get('/manifest/options', [ReportController::class, 'manifestOptions'])->name('manifest.options');
        });

        // Notifications & Announcements
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'templates'])->name('templates')->middleware('permission:notification:read');
            Route::post('/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'storeTemplate'])->name('store-template')->middleware('permission:notification:create');
            Route::post('/templates/{template}/toggle', [\App\Http\Controllers\Admin\NotificationController::class, 'toggleTemplate'])->name('toggle-template')->middleware('permission:notification:update');
            Route::get('/logs', [\App\Http\Controllers\Admin\NotificationController::class, 'logs'])->name('logs')->middleware('permission:notification:read');
            Route::get('/announcements', [\App\Http\Controllers\Admin\NotificationController::class, 'announcements'])->name('announcements')->middleware('permission:notification:read');
            Route::post('/announcements', [\App\Http\Controllers\Admin\NotificationController::class, 'storeAnnouncement'])->name('store-announcement')->middleware('permission:notification:create');
            Route::post('/announcements/{announcement}/toggle', [\App\Http\Controllers\Admin\NotificationController::class, 'toggleAnnouncement'])->name('toggle-announcement')->middleware('permission:notification:update');
            Route::delete('/announcements/{announcement}', [\App\Http\Controllers\Admin\NotificationController::class, 'destroyAnnouncement'])->name('destroy-announcement')->middleware('permission:notification:delete');
        });

        // Dual Control
        Route::prefix('dual-control')->name('dual-control.')->group(function () {
            Route::get('/', [DualControlController::class, 'index'])->name('index')->middleware('permission:dc:list');
            Route::get('/{dualControlRequest}', [DualControlController::class, 'show'])->name('show')->middleware('permission:dc:read');
            Route::post('/{dualControlRequest}/approve', [DualControlController::class, 'approve'])->name('approve')->middleware('permission:dc:approve');
            Route::post('/{dualControlRequest}/reject', [DualControlController::class, 'reject'])->name('reject')->middleware('permission:dc:approve');
        });

        // Cargo Operations (Phase 5 Admin Panel)
        Route::prefix('cargo')->name('cargo.')->group(function () {
            // Blade views
            Route::get('/dashboard',         function() { return view('admin.cargo.dashboard'); })->name('dashboard');
            Route::get('/consignments',      function() { return view('admin.cargo.consignments'); })->name('consignments');
            Route::get('/manifest',          function() { return view('admin.cargo.manifest'); })->name('manifest');
            Route::get('/types',             function() { return view('admin.cargo.types'); })->name('types');
            Route::get('/fares',             function() { return view('admin.cargo.fares'); })->name('fares');
            Route::get('/booking',           [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'bookingTerminal'])->name('booking');

            // Resourceful routes for Cargo Infrastructure
            // Stations (Cloned from Ports)
            Route::patch('stations/{station}/status', [\App\Http\Controllers\Cargo\Admin\StationController::class, 'updateStatus'])->name('stations.update-status');
            Route::resource('stations', \App\Http\Controllers\Cargo\Admin\StationController::class)->except(['destroy', 'show']);
            
            // Routes (Cloned from backend Routes)
            Route::patch('routes/{route}/status', [\App\Http\Controllers\Cargo\Admin\RouteController::class, 'updateStatus'])->name('routes.update-status');
            Route::put('routes/{route}/agents', [\App\Http\Controllers\Cargo\Admin\RouteController::class, 'updateAgents'])->name('routes.update-agents');
            Route::resource('routes', \App\Http\Controllers\Cargo\Admin\RouteController::class)->except(['destroy']);
            
            // Schedules (Cloned from backend Schedules)
            Route::resource('schedules', \App\Http\Controllers\Cargo\Admin\ScheduleController::class)->except(['show', 'edit', 'update', 'destroy']);
            
            // Journeys (Cloned from backend Voyages)
            Route::get('/journeys', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'index'])->name('journeys.index');
            Route::get('/journeys-availability', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'availabilityIndex'])->name('journeys.availability-index');
            Route::get('/journeys/{journey}/availability', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'manageAvailability'])->name('journeys.availability');
            Route::post('/journeys/{journey}/availability', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'updateAvailability'])->name('journeys.availability.update');
            Route::patch('/journeys/{journey}/status', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'updateStatus'])->name('journeys.update-status');
            Route::patch('/journeys/{journey}/weather', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'updateWeather'])->name('journeys.update-weather');
            
            // Transport Units (Cargo specific, unchanged except removing schedule tabs)
            Route::patch('transport-units/{transport_unit}/status', [\App\Http\Controllers\Cargo\Admin\TransportUnitController::class, 'updateStatus'])->name('transport-units.update-status');
            Route::resource('transport-units', \App\Http\Controllers\Cargo\Admin\TransportUnitController::class)->except(['destroy']);

            // Booking Profile (full-page Blade views with actions)
            $profileCtrl = \App\Http\Controllers\Cargo\Admin\AdminCargoController::class;
            Route::get('/bookings/{consignment}',            [$profileCtrl, 'showBookingProfile'])->name('bookings.show');
            Route::post('/bookings/{consignment}/cancel',    [$profileCtrl, 'cancelConsignment'])->name('bookings.cancel');
            Route::patch('/bookings/{consignment}/status',   [$profileCtrl, 'updateConsignmentStatus'])->name('bookings.update-status');
            Route::get('/bookings/{consignment}/print',      [$profileCtrl, 'printReceipt'])->name('bookings.print');

            // JSON API endpoints (web session auth — used by Blade JS fetch calls)
            Route::prefix('api')->name('api.')->group(function () {
                $ctrl = \App\Http\Controllers\Cargo\Admin\AdminCargoController::class;

                // Dashboard stats
                Route::get('dashboard', [$ctrl, 'dashboardStats']);

                // Consignments
                Route::get('consignments',               [$ctrl, 'getConsignments']);
                Route::get('consignments/{consignment}', [$ctrl, 'showConsignment']);

                // Cargo types CRUD
                Route::get('types',                [$ctrl, 'getCargoTypes']);
                Route::post('types',               [$ctrl, 'storeCargoType']);
                Route::put('types/{cargoType}',    [$ctrl, 'updateCargoType']);
                Route::delete('types/{cargoType}', [$ctrl, 'destroyCargoType']);

                // Cargo fares CRUD
                Route::get('fares',                [$ctrl, 'getCargoFares']);
                Route::post('fares',               [$ctrl, 'storeCargoFare']);
                Route::put('fares/{cargoFare}',    [$ctrl, 'updateCargoFare']);
                Route::delete('fares/{cargoFare}', [$ctrl, 'destroyCargoFare']);

                // Manifest
                Route::get('manifest/{transportServiceId}', [\App\Http\Controllers\Cargo\Scanner\ScannerCargoController::class, 'getManifest']);

                // Lookup dropdowns
                Route::get('routes',          [$ctrl, 'getRoutes']);
                Route::get('transport-units', [$ctrl, 'getTransportUnits']);
                Route::get('services',        [$ctrl, 'getServices']);
        Route::get('consignments',      [$ctrl, 'getConsignments']);
        Route::get('consignments/{consignment}', [$ctrl, 'showConsignment']);



                // ── Admin Booking ─────────────────────────────────
                Route::get('booking/available-services',  [$ctrl, 'getAvailableServicesForBooking']);
                Route::post('booking/calculate-fare',     [$ctrl, 'calculateFareAdmin']);
                Route::post('booking/lock-inventory',     [$ctrl, 'acquireLockAdmin']);
                Route::post('booking/release-lock',       [$ctrl, 'releaseLockAdmin']);
                Route::post('booking/confirm',            [$ctrl, 'confirmBookingAdmin']);

                // ── Payments (shared with the sales portal) ───────
                $payCtrl = \App\Http\Controllers\Cargo\CargoPaymentController::class;
                Route::get('payment-methods',                           [$payCtrl, 'methods']);
                Route::post('consignments/{consignment}/payments',      [$payCtrl, 'start']);
                Route::get('payments/{cargoPayment}',                   [$payCtrl, 'show']);
                Route::post('payments/{cargoPayment}/resend',           [$payCtrl, 'resend']);
                Route::post('payments/{cargoPayment}/confirm',          [$payCtrl, 'confirmManual']);
                Route::post('payments/{cargoPayment}/sms-instructions', [$payCtrl, 'smsInstructions']);
            });
        });
    });
});

