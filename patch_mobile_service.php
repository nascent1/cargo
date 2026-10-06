<?php

$content = file_get_contents('app/Services/MobileBookingService.php');

$startMarker = '    public function createReservation(array $payload, ?int $userId = null): Booking';
$endMarker = '    public function getBookingDetails(string $reference): array';

$startPos = strpos($content, $startMarker);
$endPos = strpos($content, $endMarker);

if ($startPos !== false && $endPos !== false) {
    $newMethod = <<<'METHOD'
    public function createReservation(array $payload, ?int $userId = null): Booking
    {
        return DB::transaction(function () use ($payload, $userId) {
            $voyage = Voyage::findOrFail($payload['voyage_id']);
            $returnVoyage = null;
            if (!empty($payload['return_voyage_id'])) {
                $returnVoyage = Voyage::findOrFail($payload['return_voyage_id']);
            }
            
            // Calculate expiry: departure time minus 2 hours (using outbound voyage)
            $expiryHours = (int) SystemConfig::getValue('reservation_expiry_hours_before_departure', 2);
            $departureDateTime = Carbon::parse($voyage->departure_date . ' ' . $voyage->departure_time);
            $expiresAt = $departureDateTime->copy()->subHours($expiryHours);

            if ($expiresAt->isPast()) {
                throw new Exception("It is too late to reserve for this voyage. Reservations close {$expiryHours} hours before departure.");
            }

            // BUG-009 fix: bounded attempts + cryptographic random_int
            $prefix = SystemConfig::getValue('ticket_prefix_default', 'VFF');
            $ref = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                $candidate = date('ymd') . str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                if (!Booking::where('booking_reference', 'like', "%-{$candidate}")->exists()) {
                    $ref = $candidate;
                    break;
                }
            }
            if (!$ref) {
                $ref = date('ymdHis') . str_pad((string) random_int(0, 9999), 4, '0', STR_PAD_LEFT);
            }
            $bookingRef = "{$prefix}-{$ref}";

            $booking = Booking::create([
                'booking_reference' => $bookingRef,
                'user_id' => $userId,
                'booking_source' => 'app',
                'status' => 'reserved',
                'payment_status' => 'pending',
                'total_amount' => 0,
                'total_amount_tzs' => 0,
                'total_amount_usd' => 0,
                'discount_amount' => 0,
                'net_amount' => 0,
                'currency' => $payload['currency'] ?? 'TZS',
                'expires_at' => $expiresAt,
            ]);

            $totalTzs = 0;
            $totalUsd = 0;
            $ticketCounter = 1;

            // Infant pricing: configurable number of free infants per adult or per booking
            $infantMode = \App\Models\SystemConfig::getValue('infant_free_mode', 'per_adult');
            $infantLimit = (int) \App\Models\SystemConfig::getValue('infant_free_limit', 1);
            
            $adultCount = collect($payload['passengers'])->filter(function ($p) {
                $pt = \App\Models\PassengerType::find($p['passenger_type_id'] ?? 1);
                $code = $pt ? strtolower($pt->code) : 'adult';
                return !str_contains($code, 'child') && !str_contains($code, 'infant');
            })->count();
            
            $totalInfants = collect($payload['passengers'])->filter(function ($p) {
                $pt = \App\Models\PassengerType::find($p['passenger_type_id'] ?? 1);
                $code = $pt ? strtolower($pt->code) : 'adult';
                return str_contains($code, 'infant');
            })->count();

            $maxFreeInfants = ($infantMode === 'per_booking') ? $infantLimit : ($adultCount * $infantLimit);
            $excessInfants = max(0, $totalInfants - $maxFreeInfants);
            $paidInfantsCount = (int) ceil($excessInfants / 2);
            
            $freeInfantsUsed = 0;
            $paidInfantsCharged = 0;
            $currency = $payload['currency'] ?? 'TZS';

            foreach ($payload['passengers'] as $passengerData) {
                // Determine passenger type code
                $pType = \App\Models\PassengerType::find($passengerData['passenger_type_id'] ?? 1);
                $typeCode = $pType ? strtolower($pType->code) : 'adult';

                $isFreeInfant = false;
                $isPaidInfant = false;

                if (str_contains($typeCode, 'infant')) {
                    if ($freeInfantsUsed < $maxFreeInfants) {
                        $isFreeInfant = true;
                        $freeInfantsUsed++;
                    } else {
                        if ($paidInfantsCharged < $paidInfantsCount) {
                            $isPaidInfant = true;
                            $paidInfantsCharged++;
                        }
                    }
                }

                $passenger = \App\Models\Passenger::create([
                    'first_name' => explode(' ', $passengerData['name'])[0] ?? 'Unknown',
                    'last_name' => explode(' ', $passengerData['name'])[1] ?? 'Unknown',
                    'passenger_type_id' => $passengerData['passenger_type_id'] ?? 1,
                    'gender' => $passengerData['gender'] ?? 'unknown',
                    'nationality_id' => $passengerData['nationality_id'] ?? 1,
                    'identity_type_id' => $passengerData['identity_type_id'] ?? null,
                    'identity_number' => $passengerData['identity_number'] ?? null,
                ]);

                // ── Outbound Leg ──
                $outboundClassId = $payload['ferry_class_id'] ?? $passengerData['ferry_class_id'];
                $outboundFareObj = \App\Models\Fare::where('route_id', $voyage->route_id)
                    ->where(function ($q) use ($voyage) {
                        $q->where('ferry_id', $voyage->ferry_id)->orWhereNull('ferry_id');
                    })
                    ->where('ferry_class_id', $outboundClassId)
                    ->first();

                $outboundFareTzs = 0;
                $outboundFareUsd = 0;

                if (str_contains($typeCode, 'infant')) {
                    if ($isPaidInfant) {
                        $outboundFareTzs = $outboundFareObj ? (float) $outboundFareObj->child_price : 0;
                        $outboundFareUsd = $outboundFareObj ? (float) $outboundFareObj->child_price_usd : 0;
                    }
                } elseif (str_contains($typeCode, 'child')) {
                    $outboundFareTzs = $outboundFareObj ? (float) $outboundFareObj->child_price : 0;
                    $outboundFareUsd = $outboundFareObj ? (float) $outboundFareObj->child_price_usd : 0;
                } else {
                    $outboundFareTzs = $outboundFareObj ? (float) $outboundFareObj->adult_price : 0;
                    $outboundFareUsd = $outboundFareObj ? (float) $outboundFareObj->adult_price_usd : 0;
                }

                $effectiveOutboundFare = ($currency === 'USD') ? $outboundFareUsd : $outboundFareTzs;
                $totalTzs += $outboundFareTzs;
                $totalUsd += $outboundFareUsd;
                
                $outboundSeat = $passengerData['outbound_seat'] ?? $passengerData['seat_number'] ?? null;

                Ticket::create([
                    'booking_id' => $booking->id,
                    'voyage_id' => $voyage->id,
                    'passenger_id' => $passenger->id,
                    'ferry_class_id' => $outboundClassId,
                    'ticket_number' => "{$bookingRef}-{$ticketCounter}",
                    'seat_number' => $outboundSeat,
                    'base_fare' => $effectiveOutboundFare,
                    'discount_amount' => 0,
                    'final_fare' => $effectiveOutboundFare,
                    'status' => 'pending',
                    'qr_code_token' => bin2hex(random_bytes(16)),
                ]);
                $ticketCounter++;

                // ── Return Leg ──
                if ($returnVoyage) {
                    $returnClassId = $payload['return_ferry_class_id'] ?? $outboundClassId;
                    $returnFareObj = \App\Models\Fare::where('route_id', $returnVoyage->route_id)
                        ->where(function ($q) use ($returnVoyage) {
                            $q->where('ferry_id', $returnVoyage->ferry_id)->orWhereNull('ferry_id');
                        })
                        ->where('ferry_class_id', $returnClassId)
                        ->first();

                    $returnFareTzs = 0;
                    $returnFareUsd = 0;

                    if (str_contains($typeCode, 'infant')) {
                        if ($isPaidInfant) {
                            $returnFareTzs = $returnFareObj ? (float) $returnFareObj->child_price : 0;
                            $returnFareUsd = $returnFareObj ? (float) $returnFareObj->child_price_usd : 0;
                        }
                    } elseif (str_contains($typeCode, 'child')) {
                        $returnFareTzs = $returnFareObj ? (float) $returnFareObj->child_price : 0;
                        $returnFareUsd = $returnFareObj ? (float) $returnFareObj->child_price_usd : 0;
                    } else {
                        $returnFareTzs = $returnFareObj ? (float) $returnFareObj->adult_price : 0;
                        $returnFareUsd = $returnFareObj ? (float) $returnFareObj->adult_price_usd : 0;
                    }

                    $effectiveReturnFare = ($currency === 'USD') ? $returnFareUsd : $returnFareTzs;
                    $totalTzs += $returnFareTzs;
                    $totalUsd += $returnFareUsd;
                    
                    $returnSeat = $passengerData['return_seat'] ?? null;

                    Ticket::create([
                        'booking_id' => $booking->id,
                        'voyage_id' => $returnVoyage->id,
                        'passenger_id' => $passenger->id,
                        'ferry_class_id' => $returnClassId,
                        'ticket_number' => "{$bookingRef}-{$ticketCounter}",
                        'seat_number' => $returnSeat,
                        'base_fare' => $effectiveReturnFare,
                        'discount_amount' => 0,
                        'final_fare' => $effectiveReturnFare,
                        'status' => 'pending',
                        'qr_code_token' => bin2hex(random_bytes(16)),
                    ]);
                    $ticketCounter++;
                }
            }

            $booking->update([
                'total_amount' => ($currency === 'USD') ? $totalUsd : $totalTzs,
                'total_amount_tzs' => $totalTzs,
                'total_amount_usd' => $totalUsd,
                'net_amount' => ($currency === 'USD') ? $totalUsd : $totalTzs,
            ]);

            return $booking->fresh(['items.passenger', 'items.ferryClass']);
        });
    }

METHOD;

    $newContent = substr($content, 0, $startPos) . $newMethod . substr($content, $endPos);
    file_put_contents('app/Services/MobileBookingService.php', $newContent);
    echo "Patched successfully\n";
} else {
    echo "Markers not found\n";
}
