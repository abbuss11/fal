<?php

namespace App\Notifications\Channels;

use App\Services\MobilePushService;
use Illuminate\Notifications\Notification;

class MobilePushChannel
{
    public function __construct(private readonly MobilePushService $pushService) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMobilePush')) {
            return;
        }

        /** @var array<string, mixed> $payload */
        $payload = $notification->toMobilePush($notifiable);

        $title = (string) ($payload['title'] ?? '');
        $body = (string) ($payload['body'] ?? '');
        $data = (array) ($payload['data'] ?? []);

        if ($title === '' || $body === '') {
            return;
        }

        $this->pushService->sendToUsers([$notifiable->id ?? 0], $title, $body, $data);
    }
}

