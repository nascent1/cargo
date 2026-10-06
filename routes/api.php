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
    Route::get('track/{consignment_number}', [\App\Http\Controllers\Cargo\TrackingController::class, 'track']);
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
Route::prefix('v1/scanner/cargo')->middleware(['auth:sanctum', 'throttle:90,1'])->group(function () {
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
    Route::get('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoTypes']);
    Route::post('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoType']);
    Route::put('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoType']);

    // Cargo Fares CRUD
    Route::get('fares', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoFares']);
    Route::post('fares', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoFare']);
    Route::put('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoFare']);
    Route::delete('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'destroyCargoFare']);
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
    Route::get('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoTypes']);
    Route::post('types', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoType']);
    Route::put('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoType']);
    Route::delete('types/{cargoType}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'destroyCargoType']);


    // Cargo Fares CRUD
    Route::get('fares', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'getCargoFares']);
    Route::post('fares', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'storeCargoFare']);
    Route::put('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'updateCargoFare']);
    Route::delete('fares/{cargoFare}', [\App\Http\Controllers\Cargo\Admin\AdminCargoController::class, 'destroyCargoFare']);

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
