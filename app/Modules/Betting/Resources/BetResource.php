<?php

namespace App\Modules\Betting\Resources;

use App\Modules\Wallet\Resources\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'total_amount' => $this->total_amount,
            'total_odds' => $this->total_odds,
            'potential_win' => $this->potential_win,
            'status' => $this->status,
            'rejection_reason' => $this->rejection_reason,
            'placed_at' => $this->placed_at?->toISOString(),
            'settled_at' => $this->settled_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'selections' => BetSelectionResource::collection($this->whenLoaded('selections')),
            'wallet_transactions' => WalletTransactionResource::collection($this->whenLoaded('walletTransactions')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
