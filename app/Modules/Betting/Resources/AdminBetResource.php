<?php

namespace App\Modules\Betting\Resources;

use App\Modules\Wallet\Resources\WalletTransactionResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminBetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,
            'status' => $this->status,

            'total_amount' => $this->total_amount,
            'total_odds' => $this->total_odds,
            'potential_win' => $this->potential_win,

            'rejection_reason' => $this->rejection_reason,
            'placed_at' => $this->placed_at?->toISOString(),
            'settled_at' => $this->settled_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'status' => $this->user->status,
            ]),

            'selections' => BetSelectionResource::collection($this->whenLoaded('selections')),

            'wallet_transactions' => WalletTransactionResource::collection(
                $this->whenLoaded('walletTransactions')
            ),

            'settlement_logs' => $this->whenLoaded('settlementLogs'),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
