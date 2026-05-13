<?php

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class AuthenticatedUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'status' => $this->status,
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'registered_at' => $this->created_at?->toIso8601String(),
            'wallet' => $this->whenLoaded('wallet', function () {
                if (! $this->wallet) {
                    return null;
                }

                return [
                    'currency' => $this->wallet->currency,
                    'balance' => (string) $this->wallet->balance,
                    'locked_balance' => (string) $this->wallet->locked_balance,
                ];
            }),
        ];
    }
}
