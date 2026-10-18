<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Channels\SmsChannel;
use App\Support\DynamicMailConfig;

class AdminPasswordResetNotification extends Notification
{
    use Queueable;

    protected string $temporaryPassword;

    public function __construct(string $temporaryPassword)
    {
        $this->temporaryPassword = $temporaryPassword;
    }

    public function via($notifiable)
    {
        // SMS first, so a mail failure can't stop it; email only when mail is really delivered
        return array_values(array_filter([
            !empty($notifiable->phone) ? SmsChannel::class : null,
            DynamicMailConfig::delivers() && !empty($notifiable->email) ? 'mail' : null,
        ]));
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your Account Password Has Been Reset')
            ->greeting('Hello ' . $notifiable->first_name . ',')
            ->line('An administrator has reset your password.')
            ->line('Your temporary password is: ' . $this->temporaryPassword)
            ->line('You will be required to change your password upon your next login.')
            ->action('Login', url('/admin/login'))
            ->line('If you did not expect this, please contact support immediately.');
    }

    /** SMS policy event "password_reset_by_admin" (template in Notifications › Templates). */
    public function toSms($notifiable): array
    {
        return ['event' => 'password_reset_by_admin', 'data' => [
            'first_name'         => $notifiable->first_name,
            'temporary_password' => $this->temporaryPassword,
        ]];
    }
}
