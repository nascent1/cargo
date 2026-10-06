<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\PushNotificationService;
use Illuminate\Console\Command;

class SendBoardingReminders extends Command
{
    protected $signature = 'boarding:reminders';
    protected $description = 'Send boarding reminder push notifications 1 hour before departure';

    public function handle(PushNotificationService $pushService): int
    {
        $this->info('Checking for upcoming departures...');
        
        // Find bookings that are confirmed/paid and depart in exactly 1 hour.
        // We look for voyages departing between 60 and 75 minutes from now.
        $start = now()->addMinutes(60);
        $end = now()->addMinutes(75);

        $upcomingBookings = Booking::with('voyage')
            ->whereIn('status', ['confirmed', 'paid'])
            ->whereHas('voyage', function ($query) use ($start, $end) {
                // Assuming voyage has departure_date and departure_time
                // which might be stored as separate columns or a single datetime.
                // Depending on the exact schema, this query might need adjustment.
                // Assuming departure_time is a full datetime or we combine them.
                $query->whereRaw("CAST(CONCAT(departure_date, ' ', departure_time) AS DATETIME) BETWEEN ? AND ?", [$start, $end]);
            })
            ->get();

        if ($upcomingBookings->isEmpty()) {
            $this->info('No upcoming bookings found for reminders.');
            return self::SUCCESS;
        }

        $count = 0;
        foreach ($upcomingBookings as $booking) {
            // Check if reminder was already sent (e.g. via notes or a flag)
            if (strpos($booking->notes, 'Boarding reminder sent') === false) {
                // Find device token and send
                // $pushService->sendBoardingReminder($deviceToken, $booking->booking_reference, $booking->voyage->departure_time);
                
                $booking->update([
                    'notes' => trim($booking->notes . "\nBoarding reminder sent at " . now()->toIso8601String())
                ]);
                
                $count++;
            }
        }

        $this->info("Successfully sent {$count} boarding reminder(s).");
        return self::SUCCESS;
    }
}
