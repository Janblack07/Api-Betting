<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        /** @var WalletCreatorInterface $walletCreator */
        $walletCreator = app(WalletCreatorInterface::class);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('Admin123!@#'),
                'status' => 'active',
            ]
        );

        $admin->syncRoles(['admin']);
        $walletCreator->createForUser($admin);

        $operator = User::query()->firstOrCreate(
            ['email' => 'operator@example.com'],
            [
                'name' => 'Operador Demo',
                'password' => Hash::make('Operator123!@#'),
                'status' => 'active',
            ]
        );

        $operator->syncRoles(['operator']);
        $walletCreator->createForUser($operator);

        $customer = User::query()->firstOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name' => 'Cliente Demo',
                'password' => Hash::make('Cliente123!@#'),
                'status' => 'active',
            ]
        );

        $customer->syncRoles(['customer']);
        $walletCreator->createForUser($customer);
    }
}
