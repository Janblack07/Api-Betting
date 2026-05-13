<?php

namespace App\Modules\Odds\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SportEventStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $sportKey,
        public readonly int $eventId,
        public readonly string $externalEventId,
        public readonly string $status,
        public readonly bool $isLive,
        public readonly bool $isActive
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('events.' . $this->sportKey);
    }

    public function broadcastAs(): string
    {
        return 'event.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'sport_key' => $this->sportKey,
            'event_id' => $this->eventId,
            'external_event_id' => $this->externalEventId,
            'status' => $this->status,
            'is_live' => $this->isLive,
            'is_active' => $this->isActive,
            'updated_at' => now()->toISOString(),
        ];
    }
}
