<?php

namespace App\Http\Controllers\Cargo;

use App\Http\Controllers\Controller;
use App\Services\Cargo\PublicTrackingService;
use App\Services\SmsNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public cargo tracking (no login): the page linked from booking SMS and receipt QR codes, and
 * the same data as JSON. Only PublicTrackingService's public-safe fields are ever returned.
 */
class TrackingController extends Controller
{
    public function __construct(private PublicTrackingService $tracking)
    {
    }

    /**
     * /track (search form) and /track/{number}.
     */
    public function view(Request $request, ?string $consignmentNumber = null)
    {
        $searched = $request->query('number');
        if ($consignmentNumber === null && filled($searched)) {
            $number = strtoupper(trim((string) $searched));
            if (PublicTrackingService::isValidNumber($number)) {
                return redirect()->route('tracking.show', $number);
            }
            $consignmentNumber = (string) $searched;
        }

        $result = $consignmentNumber !== null ? $this->tracking->lookup($consignmentNumber) : null;

        return response()->view('cargo.tracking.index', [
            'number' => $consignmentNumber,
            'result' => $result,
            'company' => SmsNotifier::companyName(),
        ], $consignmentNumber !== null && !$result ? 404 : 200);
    }

    /**
     * GET /api/v1/cargo/track/{number}
     */
    public function track(string $consignmentNumber): JsonResponse
    {
        $result = $this->tracking->lookup($consignmentNumber);

        return $result
            ? response()->json($result)
            : response()->json(['error' => 'Booking not found. Check the number on your SMS or receipt.'], 404);
    }
}
