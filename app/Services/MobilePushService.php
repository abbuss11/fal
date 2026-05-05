<?php

namespace App\Services;

use App\Models\MobileDeviceToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MobilePushService
{
    /**
     * @param iterable<int, int|string|null> $userIds
     * @param array<string, mixed> $data
     */
    public function sendToUsers(iterable $userIds, string $title, string $body, array $data = []): void
    {
        if (! config('services.mobile_push.enabled')) {
            return;
        }

        $serverKey = (string) config('services.mobile_push.fcm_server_key');
        $endpoint = (string) config('services.mobile_push.fcm_endpoint');

        if ($serverKey === '' || $endpoint === '') {
            return;
        }

        $normalizedUserIds = collect($userIds)
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        if ($normalizedUserIds->isEmpty()) {
            return;
        }

        $tokens = MobileDeviceToken::query()
            ->whereIn('user_id', $normalizedUserIds)
            ->whereNull('revoked_at')
            ->pluck('device_token');

        foreach ($tokens as $token) {
            try {
                Http::withHeaders([
                    'Authorization' => 'key='.$serverKey,
                    'Content-Type' => 'application/json',
                ])->post($endpoint, [
                    'to' => $token,
                    'priority' => 'high',
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $data,
                ]);
            } catch (\Throwable $exception) {
                Log::warning('Mobile push send failed', [
                    'token_tail' => substr((string) $token, -8),
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}

