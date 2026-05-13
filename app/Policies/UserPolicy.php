<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function moderate(User $actor, User $target): bool
    {
        return $actor->hasRole('admin') && ! $actor->is($target);
    }
}
