<?php

namespace App\Modules\Wallet\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WalletResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $balance = (float) $this->balance;
        $lockedBalance = (float) $this->locked_balance;
        $availableBalance = $balance - $lockedBalance;

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'balance' => number_format($balance, 2, '.', ''),
            'locked_balance' => number_format($lockedBalance, 2, '.', ''),
            'available_balance' => number_format($availableBalance, 2, '.', ''),
            'currency' => $this->currency,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
