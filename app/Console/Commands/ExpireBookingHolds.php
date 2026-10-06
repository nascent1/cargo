<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\BookingLog;
use App\Models\BookingLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ExpireBookingHolds extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bookings:expire-holds
                            {--dry-run : Show what would be expired without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Automatically expire held/pending bookings past their expiry time, release locked seats, and log actions';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        $this->info('Checking for expired seat holds...');

        $expiredBookings = Booking::expiredHolds()->get();

        if ($expiredBookings->isEmpty()) {
            $this->info('No expired seat holds found.');
            return self::SUCCESS;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Found {$expiredBookings->count()} expired hold(s) to process.");

        $expiredCount = 0;
        $seatsReleased = 0;

        foreach ($expiredBookings as $booking) {
            if ($dryRun) {
                $this->line("  Would expire: {$booking->booking_reference} (expired at {$booking->expires_at})");
                $expiredCount++;
                continue;
            }

            DB::transaction(function () use ($booking, &$expiredCount, &$seatsReleased) {
                // Update booking status
                $booking->update([
                    'status' => 'expired',
                    'notes' => trim($booking->notes . "\nExpired automatically by bookings:expire-holds at " . now()->toIso8601String()),
                ]);

                // Expire all held/pending tickets
                $booking->items()
                    ->whereIn('status', ['held', 'pending', 'reserved'])
                    ->update([
                        'status' => 'expired',
                    ]);

                // Release expired seat locks (locks are session-based, not booking-based)
                $released = BookingLock::where('user_id', $booking->booked_by_user_id ?? $booking->user_id)
                    ->where('expires_at', '<', now())
                    ->delete();
                $seatsReleased += $released;

                // Record in booking log
                BookingLog::create([
                    'booking_id' => $booking->id,
                    'action' => 'Hold Expired',
                    'description' => "Booking hold expired at {$booking->expires_at}. {$released} seat lock(s) released.",
                ]);

                $expiredCount++;

                // Notify customer their hold expired
                \App\Events\BookingExpired::dispatch($booking);
            });
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . "Successfully expired {$expiredCount} booking hold(s), released {$seatsReleased} seat lock(s).");

        return self::SUCCESS;
    }
}
