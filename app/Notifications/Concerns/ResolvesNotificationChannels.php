<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\MobilePushChannel;

trait ResolvesNotificationChannels
{
    /**
     * @return array<int, string>
     */
    protected function resolveChannels(object $notifiable): array
    {
        $channels = ['database'];

        if (($notifiable->notify_email ?? true) === true) {
            $channels[] = 'mail';
        }

        if (($notifiable->notify_realtime ?? true) === true) {
            $channels[] = 'broadcast';
        }

        if (($notifiable->notify_push ?? true) === true) {
            $channels[] = MobilePushChannel::class;
        }

        return array_values(array_unique($channels));
    }
}

