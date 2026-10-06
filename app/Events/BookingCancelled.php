<?php

namespace App\Events;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BookingCancelled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Booking $booking,
        public ?User $actor = null,
        public string $reason = '',
        public float $refundAmount = 0.0,
        public float $penaltyAmount = 0.0,
    ) {}
}
