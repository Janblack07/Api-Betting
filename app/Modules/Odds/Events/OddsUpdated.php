<?php

namespace App\Modules\Odds\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OddsUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly string $sportKey,
        public readonly int $eventId,
        public readonly string $externalEventId,
        public readonly array $summary,
        public readonly array $odds = []
    ) {
    }

    public function broadcastOn(): Channel
    {
        return new Channel('odds.' . $this->sportKey);
    }

    public function broadcastAs(): string
    {
        return 'odds.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'sport_key' => $this->sportKey,
            'event_id' => $this->eventId,
            'external_event_id' => $this->externalEventId,
            'summary' => $this->summary,
            'odds' => $this->odds,
            'updated_at' => now()->toISOString(),
        ];
    }
}
