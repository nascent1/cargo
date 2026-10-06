<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Channels\SmsChannel;

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
        $channels = ['mail'];
        
        if (!empty($notifiable->phone)) {
            $channels[] = SmsChannel::class;
        }
        
        return $channels;
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

    public function toSms($notifiable)
    {
        return "Hello {$notifiable->first_name}, your password has been reset by an administrator. Your temporary password is: {$this->temporaryPassword}. Please login to change it.";
    }
}
