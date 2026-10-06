<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketRescheduled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public Booking $booking,
        public ?User $actor = null,
        public ?int $oldVoyageId = null,
        public ?int $newVoyageId = null,
        public float $fareDifference = 0.0,
        public bool $wasSplit = false,
        public ?Booking $newBooking = null,
    ) {}
}
