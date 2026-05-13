<?php

namespace App\Modules\Wallet\Contracts;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;

interface WalletCreatorInterface
{
    public function createForUser(User $user): Wallet;
}
