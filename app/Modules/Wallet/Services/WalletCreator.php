<?php

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use App\Modules\Wallet\Models\Wallet;

class WalletCreator implements WalletCreatorInterface
{
    public function createForUser(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => '0.00',
                'locked_balance' => '0.00',
                'currency' => 'USD',
            ]
        );
    }
}
