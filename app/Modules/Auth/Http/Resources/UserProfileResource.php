<?php

namespace App\Modules\Auth\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Perfil HU-04: rol, estado de cuenta, fecha de registro, sin contraseña.
 *
 * @mixin \App\Models\User
 */
class UserProfileResource extends JsonResource
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
            'roles' => $this->whenLoaded('roles', fn () => $this->roles->pluck('name')->values()),
            'account_status' => $this->status,
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
            'blocked_at' => $this->blocked_at?->toIso8601String(),
            'blocked_reason' => $this->when(
                $this->blocked_reason,
                $this->blocked_reason
            ),
        ];
    }
}
