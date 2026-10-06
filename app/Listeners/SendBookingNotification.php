<?php

namespace App\Listeners;

use App\Events\BookingCancelled;
use App\Events\BookingConfirmed;
use App\Events\BookingExpired;
use App\Events\TicketRescheduled;
use App\Models\SystemConfig;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Sends SMS and Email notifications for booking lifecycle events.
 *
 * Controlled by SystemConfig: sms_enabled, email_notifications_enabled.
 * Gracefully degrades if SMS/email services are not configured.
 */
class SendBookingNotification
{
    public function handleBookingConfirmed(BookingConfirmed $event): void
    {
        $booking = $event->booking;
        $phone = $booking->user?->phone;
        $email = $booking->user?->email;

        $message = "Your ferry booking {$booking->booking_reference} has been confirmed. "
                 . "Travel date: {$booking->travel_date}. "
                 . "Amount: " . number_format((float) $booking->net_amount, 2) . " {$booking->currency}.";

        $this->sendSms($phone, $message);
        $this->sendEmail($email, 'Booking Confirmed — ' . $booking->booking_reference, $message);
    }

    public function handleBookingCancelled(BookingCancelled $event): void
    {
        $booking = $event->booking;
        $phone = $booking->user?->phone;
        $email = $booking->user?->email;

        $message = "Your ferry booking {$booking->booking_reference} has been cancelled.";
        if ($event->refundAmount > 0) {
            $message .= " Refund: " . number_format($event->refundAmount, 2) . " {$booking->currency}.";
        }
        if ($event->penaltyAmount > 0) {
            $message .= " Penalty: " . number_format($event->penaltyAmount, 2) . " {$booking->currency}.";
        }

        $this->sendSms($phone, $message);
        $this->sendEmail($email, 'Booking Cancelled — ' . $booking->booking_reference, $message);

        // Alert Admins on high-value cancellations
        $threshold = (float) \App\Models\SystemConfig::getValue('high_value_cancellation_threshold', 500000);
        if ((float) $booking->net_amount >= $threshold) {
            Log::alert('HIGH VALUE CANCELLATION', [
                'booking_reference' => $booking->booking_reference,
                'booking_id'        => $booking->id,
                'amount'            => $booking->net_amount,
                'currency'          => $booking->currency,
                'threshold'         => $threshold,
            ]);

            // Audit trail for compliance
            \App\Models\AuditLog::createEntry(
                $event->cancelledByUserId ?? null,
                'high_value_cancellation_alert',
                'Booking',
                $booking->id,
                null,
                [
                    'amount' => $booking->net_amount,
                    'currency' => $booking->currency,
                    'threshold' => $threshold,
                ],
                request()?->ip() ?? '127.0.0.1'
            );

            $adminEmails = \App\Models\User::where('domain', 'system_user')->pluck('email')->filter()->toArray();
            if (!empty($adminEmails)) {
                $adminMsg = "HIGH VALUE CANCELLATION ALERT: Booking {$booking->booking_reference} of amount " . number_format((float) $booking->net_amount, 2) . " {$booking->currency} was cancelled.";
                foreach ($adminEmails as $adminEmail) {
                    $this->sendEmail($adminEmail, 'ALERT: High Value Cancellation', $adminMsg);
                }
            }
        }
    }

    public function handleTicketRescheduled(TicketRescheduled $event): void
    {
        $booking = $event->booking;
        $ticket = $event->ticket;
        $phone = $ticket->passenger?->phone ?? $booking->user?->phone;
        $email = $ticket->passenger?->email ?? $booking->user?->email;

        $passengerName = $ticket->passenger
            ? trim($ticket->passenger->first_name . ' ' . $ticket->passenger->last_name)
            : 'Passenger';

        $message = "Hi {$passengerName}, your ticket {$ticket->ticket_number} has been rescheduled.";
        if ($event->wasSplit && $event->newBooking) {
            $message .= " New booking ref: {$event->newBooking->booking_reference}.";
        }
        if (abs($event->fareDifference) > 0.01) {
            $direction = $event->fareDifference > 0 ? 'increased' : 'decreased';
            $message .= " Fare {$direction} by " . number_format(abs($event->fareDifference), 2) . ".";
        }

        $this->sendSms($phone, $message);
        $this->sendEmail($email, 'Ticket Rescheduled — ' . $ticket->ticket_number, $message);
    }

    // ──────────────────────────────────────────────

    protected function sendSms(?string $phone, string $message): void
    {
        if (!$phone || !(bool) SystemConfig::getValue('sms_enabled', '0')) {
            return;
        }

        try {
            // Uses Kilakona or configured SMS gateway
            $smsService = app(\App\Services\SmsService::class);
            $smsService->send($phone, $message);
        } catch (\Exception $e) {
            Log::warning("SMS notification failed: {$e->getMessage()}", ['phone' => $phone]);
        }
    }

    protected function sendEmail(?string $email, string $subject, string $body): void
    {
        if (!$email || !(bool) SystemConfig::getValue('email_notifications_enabled', '0')) {
            return;
        }

        try {
            Mail::raw($body, function ($msg) use ($email, $subject) {
                $msg->to($email)->subject($subject);
            });
        } catch (\Exception $e) {
            Log::warning("Email notification failed: {$e->getMessage()}", ['email' => $email]);
        }
    }

    public function handleBookingExpired(BookingExpired $event): void
    {
        $booking = $event->booking;
        $phone = $booking->user?->phone;
        $email = $booking->user?->email;

        $message = "Your ferry booking hold {$booking->booking_reference} has expired. "
                 . "Please create a new booking if you still wish to travel.";

        $this->sendSms($phone, $message);
        $this->sendEmail($email, 'Booking Hold Expired — ' . $booking->booking_reference, $message);
    }
}
