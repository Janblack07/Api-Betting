<?php

namespace App\Modules\Auth\Services;

use App\Models\User;
use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Exceptions\InactiveAccountException;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AuthService implements AuthServiceInterface
{
    public function __construct(
        private readonly WalletCreatorInterface $walletCreator,
    ) {}

    public function register(array $data): array
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::query()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'status' => 'active',
            ]);

            $user->assignRole('customer');
            $this->walletCreator->createForUser($user);

            return $user;
        });

        $user->load('roles', 'wallet');

        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function login(array $credentials): ?array
    {
        $user = User::query()
            ->where('email', $credentials['email'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return null;
        }

        if (! $user->allowsApiAuthentication()) {
            throw new InactiveAccountException;
        }

        $user->load('roles', 'wallet');

        $token = $user->createToken('auth')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(Request $request): void
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }
    }
}
