<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — UCLP Cargo Platform
|--------------------------------------------------------------------------
*/

// ═══════════════════════════════════════════════════════════
// PUBLIC TRACKING API
// ═══════════════════════════════════════════════════════════
// Mobile-money provider callbacks (USSD push results) — public, authenticated per driver
Route::post('v1/payments/callback/{driver}', \App\Http\Controllers\Cargo\PaymentCallbackController::class)
    ->middleware('throttle:120,1')
    ->name('payments.callback');

Route::prefix('v1/cargo')->group(function () {
    // Public-safe fields only; rate limited so booking numbers can't be guessed in bulk
    Route::get('track/{consignment_number}', [\App\Http\Controllers\Cargo\TrackingController::class, 'track'])
        ->middleware('throttle:30,1')->name('api.tracking');
});

// ═══════════════════════════════════════════════════════════
// PORTAL API (Sales POS & Agent Web App)
// ═══════════════════════════════════════════════════════════
Route::prefix('v1/portal/cargo')->middleware(['auth:sanctum', 'throttle:90,1'])->group(function () {
    
    // Config & Lookups
    Route::get('types', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'getCargoTypes']);
    Route::get('availability', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'getAvailableServices']);
    Route::get('services', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'getAvailableServices']);
    Route::get('dashboard-summary', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'dashboardSummary']);
    
    // Booking Flow
    Route::post('calculate-fare', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'calculateFare']);
    Route::post('lock-inventory', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'acquireLock']);
    Route::post('release-lock', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'releaseLock']);
    Route::post('confirm-booking', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'confirmBooking']);
    Route::post('cancel/{consignment}', [\App\Http\Controllers\Cargo\Portal\PortalCargoController::class, 'cancelBooking']);
});

// ═══════════════════════════════════════════════════════════
// SCANNER API (Mobile Devices at Port)
// ═══════════════════════════════════════════════════════════
// Port staff only (the scanner used to accept any signed-in API user)
Route::prefix('v1/scanner/cargo')->middleware(['auth:sanctum', 'throttle:90,1', 'permission:cargo-manifest|cargo-load|cargo-offload|cargo-collect'])->group(function () {
    Route::post('scan', [\App\Http\Controllers\Cargo\Scanner\ScannerCargoController::class, 'scan']);
    Route::post('status/update', [\App\Http\Controllers\Cargo\Scanner\ScannerCargoController::class, 'updateStatus']);
    Route::get('manifest/{transportServiceId}', [\App\Http\Controllers\Cargo\Scanner\ScannerCargoController::class, 'getManifest']);
});

// ═══════════════════════════════════════════════════════════
// ADMIN API (Management Dashboard) — Sanctum token auth (portal/mobile)
// ═══════════════════════════════════════════════════════════
Route::prefix('v1/admin/cargo')->middleware(['auth:sanctum', 'throttle:90,1'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'dashboardStats']);
    Route::get('consignments', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getConsignments']);
    Route::get('consignments/{consignment}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'showConsignment']);

    // Cargo Types CRUD
    Route::get('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoTypes'])->middleware('permission:cargo-view|cargo-manage-types|cargo-manage-fares');
    Route::post('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoType'])->middleware('permission:cargo-manage-types');
    Route::put('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoType'])->middleware('permission:cargo-manage-types');

    // Cargo Fares CRUD
    // Same rules as the admin screens: permission-checked, and every change goes through preview + dual control
    Route::get('fares', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'index'])->middleware('permission:cargo-manage-fares|cargo-manage-types');
    Route::post('fares', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'store'])->middleware('permission:cargo-manage-fares');
    Route::put('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'revise'])->middleware('permission:cargo-manage-fares');
    Route::delete('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'destroy'])->middleware('permission:cargo-manage-fares');
});

// ═══════════════════════════════════════════════════════════
// ADMIN API (Web session auth) — for Blade admin panel pages
// The Blade views use fetch() with session cookies, not tokens
// ═══════════════════════════════════════════════════════════
Route::prefix('v1/admin/cargo')->middleware(['web', 'auth', 'throttle:90,1'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'dashboardStats']);
    Route::get('consignments', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getConsignments']);
    Route::get('consignments/{consignment}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'showConsignment']);

    // Cargo Types CRUD
    Route::get('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoTypes'])->middleware('permission:cargo-view|cargo-manage-types|cargo-manage-fares');
    Route::post('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoType'])->middleware('permission:cargo-manage-types');
    Route::put('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoType'])->middleware('permission:cargo-manage-types');
    Route::delete('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'destroyCargoType'])->middleware('permission:cargo-manage-types');


    // Cargo Fares CRUD
    // Same rules as the admin screens: permission-checked, and every change goes through preview + dual control
    Route::get('fares', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'index'])->middleware('permission:cargo-manage-fares|cargo-manage-types');
    Route::post('fares', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'store'])->middleware('permission:cargo-manage-fares');
    Route::put('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'revise'])->middleware('permission:cargo-manage-fares');
    Route::delete('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\CargoFareController::class, 'destroy'])->middleware('permission:cargo-manage-fares');

    // Manifest (scanner endpoint re-exposed for admin Blade)
    Route::get('manifest/{transportServiceId}', [\App\Http\Controllers\Cargo\Scanner\ScannerCargoController::class, 'getManifest']);

    // Lookups for dropdowns in Blade forms
    Route::get('routes', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getRoutes']);
    Route::get('transport-units', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getTransportUnits']);
});


// Base auth routes
Route::post('v1/portal/login', [\App\Http\Controllers\Api\Portal\AuthController::class, 'login']);
Route::post('v1/scanner/login', [\App\Http\Controllers\Api\Portal\AuthController::class, 'login']); // Scanner uses same auth
Route::middleware('auth:sanctum')->group(function () {
    Route::get('v1/portal/me', [\App\Http\Controllers\Api\Portal\AuthController::class, 'me']);
    Route::post('v1/portal/logout', [\App\Http\Controllers\Api\Portal\AuthController::class, 'logout']);
    Route::post('v1/scanner/logout', [\App\Http\Controllers\Api\Portal\AuthController::class, 'logout']);
});
