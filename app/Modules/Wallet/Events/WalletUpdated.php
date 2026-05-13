<?php

namespace App\Modules\Wallet\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WalletUpdated implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly int $walletId,
        public readonly string $balance,
        public readonly string $lockedBalance,
        public readonly string $currency,
        public readonly ?string $transactionType = null
    ) {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.' . $this->userId . '.wallet');
    }

    public function broadcastAs(): string
    {
        return 'wallet.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'wallet_id' => $this->walletId,
            'balance' => $this->balance,
            'locked_balance' => $this->lockedBalance,
            'currency' => $this->currency,
            'transaction_type' => $this->transactionType,
            'updated_at' => now()->toISOString(),
        ];
    }
}
