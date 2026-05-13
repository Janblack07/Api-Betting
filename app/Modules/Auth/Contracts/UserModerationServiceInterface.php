<?php

namespace App\Modules\Auth\Contracts;

use App\Models\User;

interface UserModerationServiceInterface
{
    /**
     * @param  'blocked'|'suspended'|'active'  $status
     */
    public function setAccountStatus(User $actor, User $target, string $status, ?string $reason = null): User;
}
