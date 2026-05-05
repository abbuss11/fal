<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProjectWorkspaceUpdated implements ShouldBroadcastNow
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $projectId,
        public readonly string $reason = 'workspace_updated',
        public readonly array $meta = [],
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('project.'.$this->projectId),
            new PrivateChannel('admin.tasks'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ProjectWorkspaceUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'project_id' => $this->projectId,
            'reason' => $this->reason,
            'meta' => $this->meta,
            'sent_at' => now()->toDateTimeString(),
        ];
    }
}
