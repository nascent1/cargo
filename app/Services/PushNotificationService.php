<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
// We'd use Notification facade or direct FCM HTTP request here.
// For now, implementing as a mockable service.
// In reality you would use laravel-notification-channels/fcm or similar.

class PushNotificationService
{
    /**
     * Send reservation confirmed push notification.
     */
    public function sendReservationConfirmed(string $deviceToken, string $reference, string $expiresAt): void
    {
        $this->sendPush($deviceToken, [
            'title' => 'Reservation Confirmed',
            'body' => "Your reservation {$reference} is confirmed. Please pay before {$expiresAt}.",
            'data' => ['type' => 'reservation_confirmed', 'reference' => $reference]
        ]);
    }

    /**
     * Send reservation expired push notification.
     */
    public function sendReservationExpired(string $deviceToken, string $reference): void
    {
        $this->sendPush($deviceToken, [
            'title' => 'Reservation Expired',
            'body' => "Your reservation {$reference} has expired.",
            'data' => ['type' => 'reservation_expired', 'reference' => $reference]
        ]);
    }

    /**
     * Send payment confirmed push notification.
     */
    public function sendPaymentConfirmed(string $deviceToken, string $reference): void
    {
        $this->sendPush($deviceToken, [
            'title' => 'Payment Confirmed',
            'body' => "Payment for booking {$reference} was successful.",
            'data' => ['type' => 'payment_confirmed', 'reference' => $reference]
        ]);
    }

    /**
     * Send boarding reminder push notification.
     */
    public function sendBoardingReminder(string $deviceToken, string $reference, string $departureTime): void
    {
        $this->sendPush($deviceToken, [
            'title' => 'Boarding Reminder',
            'body' => "Your ferry departs at {$departureTime}. Please proceed to the boarding gate.",
            'data' => ['type' => 'boarding_reminder', 'reference' => $reference]
        ]);
    }

    /**
     * Internal generic push sender.
     */
    protected function sendPush(string $deviceToken, array $payload): void
    {
        // TODO: Integrate FCM v1 SDK or Laravel Notification Channels
        Log::info("FCM Push to {$deviceToken}: " . json_encode($payload));
    }
}
