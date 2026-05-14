<?php

namespace App\Modules\Wallet\Services;

use App\Models\User;
use App\Modules\Admin\Events\AdminDashboardUpdated;
use App\Modules\Wallet\Contracts\WalletCreatorInterface;
use App\Modules\Wallet\Events\WalletUpdated;
use App\Modules\Wallet\Models\Wallet;
use App\Modules\Wallet\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService implements WalletCreatorInterface
{
    public function createForUser(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance' => 0,
                'locked_balance' => 0,
                'currency' => 'USD',
            ]
        );
    }

    public function getUserWallet(User $user): Wallet
    {
        return $this->createForUser($user)->refresh();
    }

    public function deposit(
        User $user,
        float $amount,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Wallet {
        $this->assertPositiveAmount($amount);

        $wallet = DB::transaction(function () use ($user, $amount, $description, $referenceType, $referenceId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (! $wallet) {
                $wallet = $this->createForUser($user);
                $wallet = Wallet::query()
                    ->where('id', $wallet->id)
                    ->lockForUpdate()
                    ->firstOrFail();
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Depósito manual.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'deposit', 'Depósito registrado correctamente.');

        event(new AdminDashboardUpdated('wallet.deposit', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => number_format($amount, 2, '.', ''),
            'balance' => (string) $wallet->balance,
        ]));

        return $wallet;
    }

    public function withdraw(
        User $user,
        float $amount,
        ?string $description = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): Wallet {
        $this->assertPositiveAmount($amount);

        $wallet = DB::transaction(function () use ($user, $amount, $description, $referenceType, $referenceId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->availableBalance() < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['El usuario no tiene saldo suficiente para realizar el retiro.'],
                ]);
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore - $amount;

            $wallet->update([
                'balance' => $balanceAfter,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description ?? 'Retiro manual.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'withdrawal', 'Retiro registrado correctamente.');

        event(new AdminDashboardUpdated('wallet.withdrawal', [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'amount' => number_format($amount, 2, '.', ''),
            'balance' => (string) $wallet->balance,
        ]));

        return $wallet;
    }

    public function holdBetAmount(User $user, float $amount, int $betId): Wallet
    {
        $this->assertPositiveAmount($amount);

        $wallet = DB::transaction(function () use ($user, $amount, $betId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($wallet->availableBalance() < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Saldo insuficiente para realizar la apuesta.'],
                ]);
            }

            $balanceBefore = (float) $wallet->balance;

            $wallet->update([
                'locked_balance' => (float) $wallet->locked_balance + $amount,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'bet_hold',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore,
                'reference_type' => 'bet',
                'reference_id' => $betId,
                'description' => 'Saldo bloqueado por apuesta.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'bet_hold', 'Saldo bloqueado por apuesta.');

        return $wallet;
    }

    public function settleBetWin(User $user, float $stakeAmount, float $payoutAmount, int $betId): Wallet
    {
        $this->assertPositiveAmount($stakeAmount);
        $this->assertPositiveAmount($payoutAmount);

        $wallet = DB::transaction(function () use ($user, $stakeAmount, $payoutAmount, $betId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->locked_balance < $stakeAmount) {
                throw ValidationException::withMessages([
                    'amount' => ['El saldo bloqueado no es suficiente para liquidar la apuesta.'],
                ]);
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore + ($payoutAmount - $stakeAmount);

            $wallet->update([
                'balance' => $balanceAfter,
                'locked_balance' => (float) $wallet->locked_balance - $stakeAmount,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'bet_win',
                'amount' => $payoutAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => 'bet',
                'reference_id' => $betId,
                'description' => 'Pago por apuesta ganadora.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'bet_win', 'Apuesta ganadora liquidada.');

        return $wallet;
    }

    public function settleBetLoss(User $user, float $stakeAmount, int $betId): Wallet
    {
        $this->assertPositiveAmount($stakeAmount);

        $wallet = DB::transaction(function () use ($user, $stakeAmount, $betId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->locked_balance < $stakeAmount) {
                throw ValidationException::withMessages([
                    'amount' => ['El saldo bloqueado no es suficiente para liquidar la apuesta.'],
                ]);
            }

            $balanceBefore = (float) $wallet->balance;
            $balanceAfter = $balanceBefore - $stakeAmount;

            $wallet->update([
                'balance' => $balanceAfter,
                'locked_balance' => (float) $wallet->locked_balance - $stakeAmount,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'bet_loss',
                'amount' => $stakeAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_type' => 'bet',
                'reference_id' => $betId,
                'description' => 'Apuesta perdida.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'bet_loss', 'Apuesta perdida liquidada.');

        return $wallet;
    }

    public function refundBet(User $user, float $stakeAmount, int $betId): Wallet
    {
        $this->assertPositiveAmount($stakeAmount);

        $wallet = DB::transaction(function () use ($user, $stakeAmount, $betId) {
            $wallet = Wallet::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ((float) $wallet->locked_balance < $stakeAmount) {
                throw ValidationException::withMessages([
                    'amount' => ['El saldo bloqueado no es suficiente para reembolsar la apuesta.'],
                ]);
            }

            $balanceBefore = (float) $wallet->balance;

            $wallet->update([
                'locked_balance' => (float) $wallet->locked_balance - $stakeAmount,
            ]);

            WalletTransaction::query()->create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $stakeAmount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore,
                'reference_type' => 'bet',
                'reference_id' => $betId,
                'description' => 'Reembolso de apuesta cancelada.',
            ]);

            return $wallet->refresh();
        });

        $this->broadcastWalletUpdated($wallet, 'refund', 'Apuesta reembolsada.');

        return $wallet;
    }

    private function assertPositiveAmount(float $amount): void
    {
        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['El monto debe ser mayor a cero.'],
            ]);
        }
    }

    private function broadcastWalletUpdated(Wallet $wallet, ?string $type = null, ?string $message = null): void
    {
        event(new WalletUpdated(
            userId: $wallet->user_id,
            walletId: $wallet->id,
            balance: (string) $wallet->balance,
            lockedBalance: (string) $wallet->locked_balance,
            availableBalance: number_format($wallet->availableBalance(), 2, '.', ''),
            currency: $wallet->currency,
            transactionType: $type,
            message: $message,
        ));
    }
}
