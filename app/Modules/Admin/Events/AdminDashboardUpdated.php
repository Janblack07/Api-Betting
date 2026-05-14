<?php

namespace App\Modules\Admin\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminDashboardUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $type,
        public readonly array $payload
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('admin.dashboard');
    }

    public function broadcastAs(): string
    {
        return 'admin.dashboard.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'payload' => $this->payload,
            'updated_at' => now()->toISOString(),
        ];
    }
}
