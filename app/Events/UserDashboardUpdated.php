<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $reason = 'dashboard_updated',
        public readonly array $meta = [],
    ) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('dashboard.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'UserDashboardUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'reason' => $this->reason,
            'meta' => $this->meta,
            'sent_at' => now()->toDateTimeString(),
        ];
    }
}
