<?php

namespace App\Support\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Throwable;

trait SendsNotificationsSafely
{
    protected function notifySafely(User $recipient, Notification $notification, array $context = []): void
    {
        try {
            $recipient->notify($notification);
        } catch (Throwable $exception) {
            Log::warning('Notification delivery failed.', [
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
                'notification' => $notification::class,
                'error' => $exception->getMessage(),
                ...$context,
            ]);
        }
    }
}

