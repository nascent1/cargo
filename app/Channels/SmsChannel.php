<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use App\Services\SmsNotifier;
use App\Services\SmsService;

/**
 * Notification channel for SMS.
 *
 * toSms() may return ['event' => 'password_reset_link', 'data' => [...]] to go through the
 * SMS policy (template, logging) via SmsNotifier, or a plain string to send as-is.
 */
class SmsChannel
{
    public function __construct(protected SmsService $smsService, protected SmsNotifier $notifier)
    {
    }

    public function send($notifiable, Notification $notification)
    {
        if (!method_exists($notification, 'toSms') || empty($notifiable->phone)) {
            return;
        }

        $message = $notification->toSms($notifiable);

        if (is_array($message) && isset($message['event'])) {
            $this->notifier->send($message['event'], $notifiable->phone, $message['data'] ?? [], $notifiable->id ?? null);
        } elseif (is_string($message) && $message !== '') {
            $this->smsService->sendSms($notifiable->phone, $message);
        }
    }
}
