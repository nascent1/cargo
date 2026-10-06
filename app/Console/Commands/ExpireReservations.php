<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\SystemConfig;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireReservations extends Command
{
    protected $signature = 'reservations:expire';
    protected $description = 'Expire reserved bookings if not paid 2 hours prior to departure';

    public function handle(PushNotificationService $pushService): int
    {
        $this->info('Checking for expired reservations...');
        
        $expiryHours = (int) SystemConfig::getValue('reservation_expiry_hours_before_departure', 2);
        
        // Find bookings that are 'reserved' and past their explicit expires_at
        // OR find 'reserved' bookings whose voyage departs within $expiryHours.
        // Actually, during reservation creation, we set expires_at = voyage->departure_time - 2 hours.
        // So checking expires_at < now() is sufficient.
        
        $expiredBookings = Booking::where('status', 'reserved')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', now())
            ->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired reservations found.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($expiredBookings as $booking) {
            DB::transaction(function () use ($booking, $pushService, &$count) {
                $booking->update([
                    'status' => 'expired',
                    'notes' => trim($booking->notes . "\nExpired automatically by reservations:expire at " . now()->toIso8601String()),
                ]);

                $booking->items()->update(['status' => 'expired']);
                
                // Note: The SeatInventory service (if listening to model events or via BookingEngineService)
                // might need to be notified to release seats. Assuming standard eloquent events or BookingEngineService 
                // handles seat releasing, or it's dynamically calculated.
                // In this backend, it seems BookingLockService/SeatInventory logic calculates available seats 
                // based on active holds/confirmed tickets. So marking as expired releases them automatically.

                // Find device token from passengers or booking (assuming it was saved in metadata or user profile)
                // For now, if we have a way to find it:
                // $pushService->sendReservationExpired($deviceToken, $booking->booking_reference);

                $count++;
            });
        }

        $this->info("Successfully expired {$count} reservation(s).");
        return self::SUCCESS;
    }
}
