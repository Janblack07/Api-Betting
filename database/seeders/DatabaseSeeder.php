<?php

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            SystemSettingSeeder::class,
        ]);
        foreach (['admin', 'customer', 'operator'] as $roleName) {
            Role::query()->firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }

        $walletCreator = app(WalletCreatorInterface::class);

        $admin = User::query()->firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'Admin123!@#',
                'status' => 'active',
            ]
        );
        $admin->syncRoles(['admin']);
        $walletCreator->createForUser($admin);

        $customer = User::query()->firstOrCreate(
            ['email' => 'cliente@example.com'],
            [
                'name' => 'Cliente Demo',
                'password' => 'Cliente123!@#',
                'status' => 'active',
            ]
        );
        $customer->syncRoles(['customer']);
        $walletCreator->createForUser($customer);
    }

}
