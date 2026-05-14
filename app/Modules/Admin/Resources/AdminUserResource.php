<?php

namespace App\Modules\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $wallet = $this->whenLoaded('wallet');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,

            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),

            'wallet' => $this->whenLoaded('wallet', fn () => [
                'id' => $wallet->id,
                'balance' => $wallet->balance,
                'locked_balance' => $wallet->locked_balance,
                'available_balance' => number_format(
                    (float) $wallet->balance - (float) $wallet->locked_balance,
                    2,
                    '.',
                    ''
                ),
                'currency' => $wallet->currency,
            ]),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
