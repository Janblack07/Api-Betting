<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Contracts\UserModerationServiceInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

class UserModerationService implements UserModerationServiceInterface
{
    public function setAccountStatus(User $actor, User $target, string $status, ?string $reason = null): User
    {
        if (! $actor->hasRole('admin')) {
            throw new AuthorizationException('Solo un administrador puede modificar el estado de la cuenta.');
        }

        if ($actor->is($target)) {
            throw new AuthorizationException('No puedes modificar tu propia cuenta desde este endpoint.');
        }

        if (! in_array($status, ['blocked', 'suspended', 'active'], true)) {
            throw new \InvalidArgumentException('Estado de cuenta no válido.');
        }

        if (in_array($status, ['blocked', 'suspended'], true) && blank($reason)) {
            throw new \InvalidArgumentException('El motivo es obligatorio al bloquear o suspender.');
        }

        return DB::transaction(function () use ($target, $status, $reason) {
            if ($status === 'active') {
                $target->forceFill([
                    'status' => 'active',
                    'blocked_at' => null,
                    'blocked_reason' => null,
                ])->save();
            } else {
                $target->forceFill([
                    'status' => $status,
                    'blocked_at' => now(),
                    'blocked_reason' => $reason,
                ])->save();
            }

            if ($status !== 'active') {
                $target->tokens()->delete();
            }

            return $target->fresh(['roles', 'wallet']);
        });
    }
}
