<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use App\Modules\Wallet\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['admin', 'customer', 'operator'] as $name) {
            Role::query()->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_register_creates_customer_wallet_and_token(): void
    {
        $payload = [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'password' => 'Secret1!@#',
            'password_confirmation' => 'Secret1!@#',
        ];

        $response = $this->postJson('/api/v1/auth/register', $payload);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'token_type', 'user']]);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);

        $user = User::query()->where('email', 'nuevo@example.com')->first();
        $this->assertTrue($user->hasRole('customer'));
        $this->assertNotNull($user->wallet);
        $this->assertSame('0.00', (string) $user->wallet->balance);
    }

    public function test_login_returns_token_for_active_user(): void
    {
        $user = User::factory()->create(['email' => 'a@b.com', 'status' => 'active']);
        $user->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'a@b.com',
            'password' => 'Password1!@#',
        ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    public function test_login_rejects_blocked_user(): void
    {
        $user = User::factory()->create([
            'email' => 'blocked@b.com',
            'status' => 'blocked',
            'blocked_at' => now(),
            'blocked_reason' => 'Fraude',
        ]);
        $user->assignRole('customer');

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'blocked@b.com',
            'password' => 'Password1!@#',
        ]);

        $response->assertForbidden();
    }

    public function test_logout_revokes_token(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/auth/logout', [], [
            'Authorization' => 'Bearer '.$token,
        ])->assertOk();

        $this->getJson('/api/v1/user/profile', [
            'Authorization' => 'Bearer '.$token,
        ])->assertUnauthorized();
    }

    public function test_profile_returns_roles_and_wallet(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $user->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $user->id,
            'balance' => '100.50',
            'locked_balance' => '0.00',
            'currency' => 'USD',
        ]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->getJson('/api/v1/user/profile', [
            'Authorization' => 'Bearer '.$token,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.account_status', 'active')
            ->assertJsonPath('data.wallet.balance', '100.50');
    }

    public function test_admin_can_block_user_and_revokes_tokens(): void
    {
        $admin = User::factory()->create(['status' => 'active', 'email' => 'admin@test.com']);
        $admin->assignRole('admin');
        Wallet::query()->create([
            'user_id' => $admin->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);

        $target = User::factory()->create(['status' => 'active', 'email' => 'victim@test.com']);
        $target->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $target->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);
        $targetToken = $target->createToken('t')->plainTextToken;

        $adminToken = $admin->createToken('a')->plainTextToken;

        $this->patchJson(
            '/api/v1/admin/users/'.$target->id.'/account',
            ['status' => 'blocked', 'reason' => 'Actividad sospechosa'],
            ['Authorization' => 'Bearer '.$adminToken]
        )->assertOk()
            ->assertJsonPath('data.status', 'blocked');

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'status' => 'blocked',
        ]);

        $this->getJson('/api/v1/user/profile', [
            'Authorization' => 'Bearer '.$targetToken,
        ])->assertUnauthorized();
    }

    public function test_customer_cannot_moderate_users(): void
    {
        $customer = User::factory()->create(['status' => 'active']);
        $customer->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $customer->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);
        $other = User::factory()->create(['status' => 'active']);
        $other->assignRole('customer');
        Wallet::query()->create([
            'user_id' => $other->id,
            'balance' => 0,
            'locked_balance' => 0,
            'currency' => 'USD',
        ]);

        $token = $customer->createToken('c')->plainTextToken;

        $this->patchJson(
            '/api/v1/admin/users/'.$other->id.'/account',
            ['status' => 'blocked', 'reason' => 'x'],
            ['Authorization' => 'Bearer '.$token]
        )->assertForbidden();
    }
}
