<?php

namespace App\Modules\Betting\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BetStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $betId,
        public readonly string $code,
        public readonly string $status,
        public readonly string $totalAmount,
        public readonly string $potentialWin,
        public readonly ?string $message = null,
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId . '.bets');
    }

    public function broadcastAs(): string
    {
        return 'bet.status.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'bet_id' => $this->betId,
            'code' => $this->code,
            'status' => $this->status,
            'total_amount' => $this->totalAmount,
            'potential_win' => $this->potentialWin,
            'message' => $this->message,
            'updated_at' => now()->toISOString(),
        ];
    }
}
