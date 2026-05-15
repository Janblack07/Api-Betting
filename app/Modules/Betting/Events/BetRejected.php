<?php

namespace App\Modules\Betting\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BetRejected implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $reason,
        public readonly string $message,
        public readonly array $errors = [],
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId . '.bets');
    }

    public function broadcastAs(): string
    {
        return 'bet.rejected';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'reason' => $this->reason,
            'message' => $this->message,
            'errors' => $this->errors,
            'created_at' => now()->toISOString(),
        ];
    }
}
