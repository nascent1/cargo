<?php

namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Models\User;
use App\Services\SmsNotifier;
use App\Support\DynamicMailConfig;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Login details for a newly created staff, agent or associate account — by SMS and email.
 */
class AccountCredentialsNotification extends Notification
{
    use Queueable;

    public function __construct(private string $password)
    {
    }

    /**
     * Notify once the surrounding transaction commits; never throws.
     */
    public static function sendAfterCommit(User $user, string $password): void
    {
        DB::afterCommit(function () use ($user, $password) {
            try {
                $user->notify(new self($password));
            } catch (Throwable $e) {
                Log::warning("Account credentials notification to user #{$user->id} failed: {$e->getMessage()}");
            }
        });
    }

    public function via($notifiable): array
    {
        $channels = [];
        if (!empty($notifiable->phone)) {
            $channels[] = SmsChannel::class; // first, so a mail failure can't stop it
        }
        if (!empty($notifiable->email) && DynamicMailConfig::delivers()) {
            $channels[] = 'mail'; // only when mail is really delivered (a log mailer would write the password to the log)
        }

        return $channels;
    }

    public function toSms($notifiable): array
    {
        return ['event' => 'account_credentials', 'data' => [
            'account_type' => $this->accountType($notifiable),
            'login_url'    => url(route('login', [], false)),
            'email'        => $notifiable->email,
            'password'     => $this->password,
        ]];
    }

    public function toMail($notifiable): MailMessage
    {
        $company = SmsNotifier::companyName();

        return (new MailMessage)
            ->subject("Your {$company} account")
            ->greeting('Hello ' . ($notifiable->first_name ?: '') . ',')
            ->line("Your {$this->accountType($notifiable)} account on {$company} is ready.")
            ->line('Email: ' . $notifiable->email)
            ->line('Password: ' . $this->password)
            ->action('Log in', url(route('login', [], false)))
            ->line('Please change the password after you log in.');
    }

    private function accountType($notifiable): string
    {
        return match ($notifiable->domain ?? null) {
            'agent'                         => 'agent',
            'associate', 'associate_agent'  => 'agent staff',
            default                         => 'staff',
        };
    }
}
