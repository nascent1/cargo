<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Collection;

class NotificationService
{
    /**
     * Send a notification to a single user.
     */
    public function send(User $user, string $type, string $channel, string $title, string $body, ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type'    => $type,
            'channel' => $channel,
            'title'   => $title,
            'body'    => $body,
            'data'    => $data,
        ]);
    }

    /**
     * Send a notification to multiple users.
     */
    public function sendBulk(Collection $users, string $type, string $channel, string $title, string $body, ?array $data = null): int
    {
        $count = 0;
        foreach ($users as $user) {
            $this->send($user, $type, $channel, $title, $body, $data);
            $count++;
        }
        return $count;
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(int $notificationId): bool
    {
        $notification = Notification::find($notificationId);
        if (!$notification) return false;
        $notification->markAsRead();
        return true;
    }

    /**
     * Get unread count for a user.
     */
    public function getUnreadCount(User $user): int
    {
        return Notification::where('user_id', $user->id)->unread()->count();
    }
}
