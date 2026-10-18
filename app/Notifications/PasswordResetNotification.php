<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Channels\SmsChannel;
use App\Support\DynamicMailConfig;
use Illuminate\Support\Facades\Lang;

class PasswordResetNotification extends Notification
{
    use Queueable;

    public $token;

    public function __construct($token)
    {
        $this->token = $token;
    }

    public function via($notifiable): array
    {
        // SMS first, so a mail failure can't stop it; email only when mail is really delivered
        return array_values(array_filter([
            !empty($notifiable->phone) ? SmsChannel::class : null,
            DynamicMailConfig::delivers() ? 'mail' : null,
        ]));
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire')]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'));
    }

    /** SMS policy event "password_reset_link" (template in Notifications › Templates). */
    public function toSms($notifiable): ?array
    {
        if (!$notifiable->phone) {
            return null;
        }

        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return ['event' => 'password_reset_link', 'data' => [
            'first_name'      => $notifiable->first_name,
            'reset_url'       => $url,
            'expires_minutes' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
        ]];
    }
}
