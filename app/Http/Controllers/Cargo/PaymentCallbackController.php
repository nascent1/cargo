<?php

namespace App\Http\Controllers\Cargo;

use App\Gateways\Payment\Push\PushGatewayManager;
use App\Http\Controllers\Controller;
use App\Services\Cargo\CargoPaymentService;
use Illuminate\Http\Request;

/**
 * Public endpoint the mobile-money providers call back: POST /api/v1/payments/callback/{driver}
 */
class PaymentCallbackController extends Controller
{
    public function __invoke(Request $request, string $driver, CargoPaymentService $payments)
    {
        abort_unless(isset(PushGatewayManager::DRIVERS[$driver]), 404);

        return $payments->handleCallback($driver, $request);
    }
}
