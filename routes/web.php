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

// Public cargo tracking (linked from booking SMS and receipt QR codes; no login)
Route::middleware('throttle:30,1')->group(function () {
    Route::get('/track', [\App\Http\Controllers\Cargo\TrackingController::class, 'view'])->name('tracking.search');
    Route::get('/track/{consignmentNumber}', [\App\Http\Controllers\Cargo\TrackingController::class, 'view'])->name('tracking.show');
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

    // Portal cargo API endpoints (web session auth; 2FA like the portal page itself)
    Route::prefix('portal/api/cargo')->middleware(['portal.access', \App\Http\Middleware\TwoFactorMiddleware::class])->group(function () {
        $ctrl = \App\Http\Controllers\Cargo\Portal\PortalCargoController::class;
        Route::get('types',           [$ctrl, 'getCargoTypes']);
        Route::get('payment-methods', [\App\Http\Controllers\Cargo\CargoPaymentController::class, 'methods']);
        Route::get('availability',    [$ctrl, 'getAvailableServices']);
        Route::get('services',        [$ctrl, 'getAvailableServices']);
        // Booking: the same flow and rules as the admin booking terminal
        Route::middleware('permission:cargo-book')->group(function () use ($ctrl) {
            Route::post('calculate-fare',  [$ctrl, 'calculateFare']);
            // Booking actions run in the clerk's shift (check_shift: starts it, or SHIFT_ENDED)
            Route::post('lock-inventory',  [$ctrl, 'acquireLock'])->middleware('check_shift');
            Route::post('release-lock',    [$ctrl, 'releaseLock']);
            Route::post('confirm-booking', [$ctrl, 'confirmBooking'])->middleware('check_shift');
            Route::get('customers',        [$ctrl, 'lookupCustomer']);
        });
        Route::post('cancel/{consignment}', [$ctrl, 'cancelBooking'])->middleware('check_shift');
        Route::get('consignments',      [$ctrl, 'getConsignments']);
        Route::get('consignments/{consignment}', [$ctrl, 'showConsignment']);
        Route::patch('consignments/{consignment}/status', [$ctrl, 'updateStatus']);
        // Hand-over at the destination: code to the collector's mobile, then record the collection
        Route::post('consignments/{consignment}/handover/code', [$ctrl, 'handoverCode'])->middleware(['permission:cargo-collect', 'throttle:6,1']);
        Route::post('consignments/{consignment}/handover',      [$ctrl, 'handover'])->middleware('permission:cargo-collect');
        Route::get('consignments/{consignment}/print',    [$ctrl, 'printReceipt']);
        Route::get('dashboard-summary', [$ctrl, 'dashboardSummary']);

        // Payments (USSD push, Lipa Namba, cash) — shared with the admin portal
        $payCtrl = \App\Http\Controllers\Cargo\CargoPaymentController::class;
        Route::post('consignments/{consignment}/payments',      [$payCtrl, 'start'])->middleware('check_shift');
        Route::get('payments/{cargoPayment}',                   [$payCtrl, 'show']);
        Route::post('payments/{cargoPayment}/resend',           [$payCtrl, 'resend'])->middleware('check_shift');
        Route::post('payments/{cargoPayment}/confirm',          [$payCtrl, 'confirmManual'])->middleware('check_shift');
        Route::post('payments/{cargoPayment}/sms-instructions', [$payCtrl, 'smsInstructions'])->middleware('check_shift');
    });

    // Sales portal: the clerk's own shift (status, end + handover summary, extension request)
    Route::prefix('portal/api/shift')->middleware(['portal.access', \App\Http\Middleware\TwoFactorMiddleware::class])->group(function () {
        $shiftCtrl = \App\Http\Controllers\Cargo\Portal\PortalShiftController::class;
        Route::get('/',         [$shiftCtrl, 'show']);
        Route::post('end',      [$shiftCtrl, 'end']);
        Route::post('extension', [$shiftCtrl, 'requestExtension'])->middleware('throttle:6,1');
    });

    // A sales officer's first portal page after signing in (past 2FA) starts their shift
    Route::view('/portal/{any?}', 'portal.spa')
        ->where('any', '.*')
        ->middleware(['portal.access', \App\Http\Middleware\TwoFactorMiddleware::class, \App\Http\Middleware\StartShiftOnSignIn::class])
        ->name('portal.spa');

    // ── Admin Panel ────────────────────────────────────────────────
    Route::prefix('admin')->name('admin.')->middleware(['admin.access', \App\Http\Middleware\TwoFactorMiddleware::class, \App\Http\Middleware\ForceExchangeRateUpdate::class])->group(function () {

        // Today's exchange rate from the rate prompt / header chip (exempt from ForceExchangeRateUpdate)
        Route::post('/exchange-rate/current', [\App\Http\Controllers\Admin\ExchangeRateController::class, 'setCurrent'])->name('exchange-rate.current')->middleware('permission:exchange_rate:maintain');

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

            // Integrations: SMS gateways, SMTP servers, SMS policy, close of business (changes go through dual control)
            $integrationCtrl = \App\Http\Controllers\Admin\IntegrationController::class;
            Route::get('/integrations', [$integrationCtrl, 'index'])->name('integrations.index')->middleware('permission:integration:read');
            Route::post('/integrations', [$integrationCtrl, 'store'])->name('integrations.store')->middleware('permission:integration:create');
            Route::post('/integrations/test', [$integrationCtrl, 'test'])->name('integrations.test')->middleware(['permission:integration:update', 'throttle:6,1']);
            Route::put('/integrations/priority/{channel}', [$integrationCtrl, 'priority'])->name('integrations.priority')->middleware('permission:integration:update');
            Route::put('/integrations/{integration}', [$integrationCtrl, 'update'])->name('integrations.update')->middleware('permission:integration:update');
            Route::patch('/integrations/{integration}/status', [$integrationCtrl, 'status'])->name('integrations.status')->middleware('permission:integration:update');
            Route::delete('/integrations/{integration}', [$integrationCtrl, 'destroy'])->name('integrations.destroy')->middleware('permission:integration:delete');
            $closureCtrl = \App\Http\Controllers\Admin\BusinessDayClosureController::class;
            Route::get('/integrations/close-of-business/preview', [$closureCtrl, 'preview'])->name('integrations.cob.preview')->middleware('permission:integration:read');
            Route::post('/integrations/close-of-business/send-to-me', [$closureCtrl, 'sendToMe'])->name('integrations.cob.send-to-me')->middleware(['permission:integration:update', 'throttle:3,1']);
            Route::post('/integrations/close-of-business/{closure}/resend', [$closureCtrl, 'resend'])->name('integrations.cob.resend')->middleware('permission:integration:update');

            // Change Password (available to all authenticated users)
            Route::get('/change-password', [SystemConfigController::class, 'showChangePassword'])->name('change-password');
            Route::post('/change-password', [SystemConfigController::class, 'changePassword'])->name('change-password.update');

            // Role Management
            Route::post('/roles', [SystemConfigController::class, 'createRole'])->name('roles.create')->middleware('permission:role:manage');
            Route::put('/roles/{role}', [SystemConfigController::class, 'updateRole'])->name('roles.update')->middleware('permission:role:manage');
            Route::delete('/roles/{role}', [SystemConfigController::class, 'deleteRole'])->name('roles.delete')->middleware('permission:role:manage');
        });

        // The ferry screens these addresses had (voyages, availability) don't exist here: cargo's are Journeys
        Route::redirect('/voyages', '/admin/cargo/journeys');
        Route::redirect('/availability', '/admin/cargo/journeys-availability');

        // Cargo reports (exports additionally need cargo-export-report, checked in the controller)
        Route::prefix('reports')->name('reports.')->middleware('permission:cargo-report')->group(function () {
            Route::get('/', [ReportController::class, 'index'])->name('index');
            Route::get('/{report}', [ReportController::class, 'show'])->name('show')->where('report', '[a-z_]+');
        });

        // Notifications (announcements are a ferry screen with no model or table here)
        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'templates'])->name('templates')->middleware('permission:notification:read');
            Route::post('/templates', [\App\Http\Controllers\Admin\NotificationController::class, 'storeTemplate'])->name('store-template')->middleware('permission:notification:create');
            Route::post('/templates/{template}/toggle', [\App\Http\Controllers\Admin\NotificationController::class, 'toggleTemplate'])->name('toggle-template')->middleware('permission:notification:update');
            Route::get('/logs', [\App\Http\Controllers\Admin\NotificationController::class, 'logs'])->name('logs')->middleware('permission:notification:read');
        });

        // Dual Control
        Route::prefix('dual-control')->name('dual-control.')->group(function () {
            Route::get('/', [DualControlController::class, 'index'])->name('index')->middleware('permission:dc:list');
            Route::get('/{dualControlRequest}', [DualControlController::class, 'show'])->name('show')->middleware('permission:dc:read');
            Route::post('/{dualControlRequest}/approve', [DualControlController::class, 'approve'])->name('approve')->middleware('permission:dc:approve');
            Route::post('/{dualControlRequest}/reject', [DualControlController::class, 'reject'])->name('reject')->middleware('permission:dc:approve');
            // The maker or a checker (checked in DualControlService::cancel)
            Route::post('/{dualControlRequest}/cancel', [DualControlController::class, 'cancel'])->name('cancel');
        });

        // Shifts (sales-portal clerks)
        Route::prefix('shifts')->name('shifts.')->group(function () {
            $shiftCtrl = \App\Http\Controllers\Admin\ShiftController::class;
            Route::get('/',               [$shiftCtrl, 'index'])->name('index')->middleware('permission:shift:read');
            Route::get('/export',         [$shiftCtrl, 'export'])->name('export')->middleware('permission:shift:read');
            Route::get('/{shift}',        [$shiftCtrl, 'show'])->name('show')->middleware('permission:shift:read');
            Route::post('/{shift}/end',   [$shiftCtrl, 'end'])->name('end')->middleware('permission:shift:manage');
            Route::post('/{shift}/extend', [$shiftCtrl, 'extend'])->name('extend')->middleware('permission:shift:manage');
        });

        // Cargo Operations (Phase 5 Admin Panel)
        Route::prefix('cargo')->name('cargo.')->group(function () {
            $cargoCtrl = \App\Http\Controllers\Cargo\Admin\AdminCargoController::class;

            // Blade views
            Route::get('/dashboard',            [$cargoCtrl, 'dashboard'])->name('dashboard')->middleware('permission:cargo-view');
            Route::get('/consignments',         [$cargoCtrl, 'consignmentsPage'])->name('consignments')->middleware('permission:cargo-view');
            Route::get('/consignments/export',  [$cargoCtrl, 'exportConsignments'])->name('consignments.export')->middleware('permission:cargo-export-report');
            Route::get('/manifest',             [$cargoCtrl, 'manifestPage'])->name('manifest')->middleware('permission:cargo-manifest');
            Route::get('/manifest/{transportService}/export', [$cargoCtrl, 'exportManifest'])->name('manifest.export')->middleware('permission:cargo-manifest');
            Route::get('/types',                fn () => view('admin.cargo.types'))->name('types')->middleware('permission:cargo-manage-types');
            Route::get('/fares',                fn () => view('admin.cargo.fares'))->name('fares')->middleware('permission:cargo-manage-fares');
            Route::get('/fares/export',         [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'export'])->name('fares.export')->middleware('permission:cargo-manage-fares|cargo-export-report');
            Route::get('/types/{cargoType}',    [\App\Http\Controllers\Cargo\Admin\CargoTypeProfileController::class, 'show'])->name('types.show')->middleware('permission:cargo-manage-types|cargo-manage-fares');
            Route::get('/booking',              [$cargoCtrl, 'bookingTerminal'])->name('booking')->middleware('permission:cargo-book');

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
            Route::patch('/journeys/{journey}/status', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'updateStatus'])->name('journeys.update-status')->middleware('permission:voyage:update');
            // Departing / arriving moves the cargo on board (and texts senders and receivers)
            Route::post('/journeys/{journey}/depart', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'depart'])->name('journeys.depart')->middleware('permission:cargo-load');
            Route::post('/journeys/{journey}/arrive', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'arrive'])->name('journeys.arrive')->middleware('permission:cargo-offload');
            Route::patch('/journeys/{journey}/weather', [\App\Http\Controllers\Cargo\Admin\JourneyController::class, 'updateWeather'])->name('journeys.update-weather');
            
            // Transport Units (Cargo specific, unchanged except removing schedule tabs)
            Route::patch('transport-units/{transport_unit}/status', [\App\Http\Controllers\Cargo\Admin\TransportUnitController::class, 'updateStatus'])->name('transport-units.update-status');
            Route::resource('transport-units', \App\Http\Controllers\Cargo\Admin\TransportUnitController::class)->except(['destroy']);

            // Booking Profile (full-page Blade views with actions)
            Route::get('/bookings/{consignment}',            [$cargoCtrl, 'showBookingProfile'])->name('bookings.show')->middleware('permission:cargo-view');
            Route::post('/bookings/{consignment}/cancel',    [$cargoCtrl, 'cancelConsignment'])->name('bookings.cancel')->middleware('permission:cargo-cancel');
            // Port operations: load before departure, hand over on arrival, supervisors correct mistakes
            $opsCtrl = \App\Http\Controllers\Cargo\Admin\CargoOperationsController::class;
            Route::post('/bookings/{consignment}/load',          [$opsCtrl, 'load'])->name('bookings.load')->middleware('permission:cargo-load');
            Route::post('/bookings/{consignment}/handover/code', [$opsCtrl, 'handoverCode'])->name('bookings.handover.code')->middleware(['permission:cargo-collect', 'throttle:6,1']);
            Route::post('/bookings/{consignment}/handover',      [$opsCtrl, 'handover'])->name('bookings.handover')->middleware('permission:cargo-collect');
            Route::patch('/bookings/{consignment}/status',       [$opsCtrl, 'correct'])->name('bookings.update-status')->middleware('permission:cargo-status-correct');
            Route::get('/bookings/{consignment}/print',      [$cargoCtrl, 'printReceipt'])->name('bookings.print')->middleware('permission:cargo-view');

            // JSON API endpoints (web session auth — used by Blade JS fetch calls)
            Route::prefix('api')->name('api.')->group(function () use ($cargoCtrl) {
                $ctrl = $cargoCtrl;

                // Dashboard
                Route::get('dashboard', [$ctrl, 'dashboardStats'])->middleware('permission:cargo-view');

                // Consignments
                Route::get('consignments',               [$ctrl, 'getConsignments'])->middleware('permission:cargo-view');
                Route::get('consignments/{consignment}', [$ctrl, 'showConsignment'])->middleware('permission:cargo-view');

                // Cargo types CRUD
                Route::get('types',                [$ctrl, 'getCargoTypes'])->middleware('permission:cargo-view|cargo-manage-types|cargo-manage-fares');
                Route::post('types',               [$ctrl, 'storeCargoType'])->middleware('permission:cargo-manage-types');
                Route::put('types/{cargoType}',    [$ctrl, 'updateCargoType'])->middleware('permission:cargo-manage-types');
                Route::delete('types/{cargoType}', [$ctrl, 'destroyCargoType'])->middleware('permission:cargo-manage-types');

                // Cargo fares — versions with a validity window; every change is previewed, then goes through dual control
                $fareCtrl = \App\Http\Controllers\Cargo\Admin\CargoFareController::class;
                Route::middleware('permission:cargo-manage-fares|cargo-manage-types')->group(function () use ($fareCtrl) {
                    Route::get('fares',           [$fareCtrl, 'index']);
                    Route::get('fares/rate-card', [$fareCtrl, 'rateCard']);
                    Route::get('fares/series',    [$fareCtrl, 'series']);
                    Route::get('fares/rules',     [$fareCtrl, 'rules']);
                });
                Route::middleware('permission:cargo-manage-fares')->group(function () use ($fareCtrl) {
                    Route::post('fares/preview',            [$fareCtrl, 'preview']);
                    Route::post('fares',                    [$fareCtrl, 'store']);
                    Route::post('fares/value-charge',       [$fareCtrl, 'bulkValueCharge']);
                    Route::post('fares/copy',               [$fareCtrl, 'copy']);
                    Route::put('fares/{cargoFare}',         [$fareCtrl, 'revise']);
                    Route::post('fares/{cargoFare}/end',    [$fareCtrl, 'end']);
                    Route::delete('fares/{cargoFare}',      [$fareCtrl, 'destroy']);
                });

                // Manifest
                Route::middleware('permission:cargo-manifest')->group(function () use ($ctrl) {
                    Route::get('manifest-services',             [$ctrl, 'getManifestServices']);
                    Route::get('manifest/{transportServiceId}', [$ctrl, 'getManifest']);
                });
                Route::post('manifest/{transportService}/load', [\App\Http\Controllers\Cargo\Admin\CargoOperationsController::class, 'loadFromManifest'])
                    ->middleware('permission:cargo-load');

                // Lookup dropdowns
                Route::get('routes',          [$ctrl, 'getRoutes'])->middleware('permission:cargo-view');
                Route::get('transport-units', [$ctrl, 'getTransportUnits'])->middleware('permission:cargo-view');

                // ── Admin Booking ─────────────────────────────────
                Route::middleware('permission:cargo-book')->group(function () use ($ctrl) {
                    Route::get('booking/available-services',  [$ctrl, 'getAvailableServicesForBooking']);
                    Route::post('booking/quote',              [$ctrl, 'quoteBooking']);
                    Route::post('booking/lock-inventory',     [$ctrl, 'acquireLockAdmin']);
                    Route::post('booking/release-lock',       [$ctrl, 'releaseLockAdmin']);
                    Route::post('booking/confirm',            [$ctrl, 'confirmBookingAdmin']);
                    Route::get('booking/customers',           [$ctrl, 'lookupCustomer']);
                });

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

