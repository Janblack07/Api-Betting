<?php

namespace App\Modules\Betting\Services;

use App\Models\User;
use App\Modules\Admin\Events\AdminDashboardUpdated;
use App\Modules\Betting\Events\BetStatusUpdated;
use App\Modules\Betting\Models\Bet;
use App\Modules\Betting\Models\BetSettlementLog;
use App\Modules\Betting\Models\EventResult;
use App\Modules\Odds\Models\SportEvent;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BetSettlementService
{
    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    public function storeManualEventResult(array $data, User $admin): EventResult
    {
        return DB::transaction(function () use ($data, $admin) {
            $event = SportEvent::query()
                ->where('id', $data['sport_event_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $winnerName = $this->resolveWinnerName(
                event: $event,
                resultType: $data['result_type'],
                winnerName: $data['winner_name'] ?? null
            );

            $result = EventResult::query()->updateOrCreate(
                [
                    'sport_event_id' => $event->id,
                ],
                [
                    'external_event_id' => $event->external_event_id,
                    'sport_key' => $event->sport_key,
                    'home_score' => $data['home_score'] ?? null,
                    'away_score' => $data['away_score'] ?? null,
                    'winner_name' => $winnerName,
                    'result_type' => $data['result_type'],
                    'source' => 'manual',
                    'raw_payload' => [
                        'admin_id' => $admin->id,
                        'observation' => $data['observation'] ?? null,
                    ],
                    'resulted_at' => now(),
                ]
            );

            $event->update([
                'status' => $data['result_type'] === 'cancelled' ? 'cancelled' : 'completed',
                'is_live' => false,
                'is_active' => false,
            ]);

            $this->settleBetsByEventResult($result);

            event(new AdminDashboardUpdated('event.result.manual', [
                'event_id' => $event->id,
                'external_event_id' => $event->external_event_id,
                'result_type' => $result->result_type,
                'winner_name' => $result->winner_name,
                'admin_id' => $admin->id,
            ]));

            return $result->refresh();
        });
    }

    public function settleBetsByEventResult(EventResult $result): void
    {
        $bets = Bet::query()
            ->whereIn('status', ['pending', 'accepted'])
            ->whereHas('selections', function ($query) use ($result) {
                $query->where('sport_event_id', $result->sport_event_id);
            })
            ->with(['selections', 'user'])
            ->get();

        foreach ($bets as $bet) {
            $this->evaluateAndSettleBet($bet, $result);
        }
    }

    public function manualSettle(Bet $bet, string $result, string $observation, User $admin): Bet
    {
        if (! in_array($bet->status, ['pending', 'accepted'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Solo se pueden liquidar apuestas pendientes o aceptadas.'],
            ]);
        }

        return match ($result) {
            'won' => $this->settleWon($bet, 'manual', $observation, $admin),
            'lost' => $this->settleLost($bet, 'manual', $observation, $admin),
            'refunded' => $this->settleRefunded($bet, 'manual', $observation, $admin),
            default => throw ValidationException::withMessages([
                'result' => ['Resultado de liquidación no válido.'],
            ]),
        };
    }

    private function evaluateAndSettleBet(Bet $bet, EventResult $result): ?Bet
    {
        if ($result->result_type === 'cancelled') {
            return $this->settleRefunded(
                bet: $bet,
                source: 'automatic',
                observation: 'Evento cancelado. Reembolso automático.',
                admin: null
            );
        }

        $bet->loadMissing('selections');

        foreach ($bet->selections as $selection) {
            if ((int) $selection->sport_event_id !== (int) $result->sport_event_id) {
                continue;
            }

            $selectionWon = $this->selectionMatchesResult(
                selectionName: $selection->selection_name,
                result: $result
            );

            $selectionResult = $selectionWon ? 'won' : 'lost';

            $selection->update([
                'status' => $selectionResult,
                'result' => $selectionResult,
            ]);
        }

        $bet->refresh();
        $bet->load('selections');

        $hasLostSelection = $bet->selections->contains(
            fn ($selection) => $selection->result === 'lost'
        );

        $hasPendingSelection = $bet->selections->contains(
            fn ($selection) => $selection->result === null
        );

        if ($hasLostSelection) {
            return $this->settleLost(
                bet: $bet,
                source: 'automatic',
                observation: 'Apuesta liquidada como perdida por resultado de evento.',
                admin: null
            );
        }

        if (! $hasPendingSelection) {
            return $this->settleWon(
                bet: $bet,
                source: 'automatic',
                observation: 'Apuesta liquidada como ganadora.',
                admin: null
            );
        }

        return null;
    }

    private function settleWon(Bet $bet, string $source, ?string $observation, ?User $admin): Bet
    {
        return DB::transaction(function () use ($bet, $source, $observation, $admin) {
            $bet = Bet::query()
                ->where('id', $bet->id)
                ->lockForUpdate()
                ->with('user')
                ->firstOrFail();

            if (! in_array($bet->status, ['pending', 'accepted'], true)) {
                return $bet;
            }

            $previousStatus = $bet->status;

            $this->walletService->settleBetWin(
                user: $bet->user,
                stakeAmount: (float) $bet->total_amount,
                payoutAmount: (float) $bet->potential_win,
                betId: $bet->id
            );

            $bet->update([
                'status' => 'won',
                'settled_at' => now(),
            ]);

            $this->createSettlementLog(
                bet: $bet,
                admin: $admin,
                settlementType: 'won',
                source: $source,
                previousStatus: $previousStatus,
                observation: $observation
            );

            $this->broadcastBet($bet, 'Tu apuesta fue ganadora.');

            event(new AdminDashboardUpdated('bet.settled.won', [
                'bet_id' => $bet->id,
                'code' => $bet->code,
                'user_id' => $bet->user_id,
                'amount' => (string) $bet->total_amount,
                'potential_win' => (string) $bet->potential_win,
            ]));

            return $bet->load(['selections', 'walletTransactions', 'settlementLogs']);
        });
    }

    private function settleLost(Bet $bet, string $source, ?string $observation, ?User $admin): Bet
    {
        return DB::transaction(function () use ($bet, $source, $observation, $admin) {
            $bet = Bet::query()
                ->where('id', $bet->id)
                ->lockForUpdate()
                ->with('user')
                ->firstOrFail();

            if (! in_array($bet->status, ['pending', 'accepted'], true)) {
                return $bet;
            }

            $previousStatus = $bet->status;

            $this->walletService->settleBetLoss(
                user: $bet->user,
                stakeAmount: (float) $bet->total_amount,
                betId: $bet->id
            );

            $bet->update([
                'status' => 'lost',
                'settled_at' => now(),
            ]);

            $this->createSettlementLog(
                bet: $bet,
                admin: $admin,
                settlementType: 'lost',
                source: $source,
                previousStatus: $previousStatus,
                observation: $observation
            );

            $this->broadcastBet($bet, 'Tu apuesta fue perdida.');

            event(new AdminDashboardUpdated('bet.settled.lost', [
                'bet_id' => $bet->id,
                'code' => $bet->code,
                'user_id' => $bet->user_id,
                'amount' => (string) $bet->total_amount,
            ]));

            return $bet->load(['selections', 'walletTransactions', 'settlementLogs']);
        });
    }

    private function settleRefunded(Bet $bet, string $source, ?string $observation, ?User $admin): Bet
    {
        return DB::transaction(function () use ($bet, $source, $observation, $admin) {
            $bet = Bet::query()
                ->where('id', $bet->id)
                ->lockForUpdate()
                ->with('user')
                ->firstOrFail();

            if (! in_array($bet->status, ['pending', 'accepted'], true)) {
                return $bet;
            }

            $previousStatus = $bet->status;

            $this->walletService->refundBet(
                user: $bet->user,
                stakeAmount: (float) $bet->total_amount,
                betId: $bet->id
            );

            $bet->update([
                'status' => 'refunded',
                'settled_at' => now(),
            ]);

            $bet->selections()->update([
                'status' => 'refunded',
                'result' => 'refunded',
            ]);

            $this->createSettlementLog(
                bet: $bet,
                admin: $admin,
                settlementType: 'refunded',
                source: $source,
                previousStatus: $previousStatus,
                observation: $observation
            );

            $this->broadcastBet($bet, 'Tu apuesta fue reembolsada.');

            event(new AdminDashboardUpdated('bet.settled.refunded', [
                'bet_id' => $bet->id,
                'code' => $bet->code,
                'user_id' => $bet->user_id,
                'amount' => (string) $bet->total_amount,
            ]));

            return $bet->load(['selections', 'walletTransactions', 'settlementLogs']);
        });
    }

    private function selectionMatchesResult(string $selectionName, EventResult $result): bool
    {
        if ($result->result_type === 'draw') {
            return strtolower(trim($selectionName)) === 'draw'
                || strtolower(trim($selectionName)) === 'empate';
        }

        return strtolower(trim($selectionName)) === strtolower(trim((string) $result->winner_name));
    }

    private function resolveWinnerName(
        SportEvent $event,
        string $resultType,
        ?string $winnerName
    ): ?string {
        return match ($resultType) {
            'home' => $event->home_team,
            'away' => $event->away_team,
            'draw' => 'Draw',
            'cancelled' => null,
            default => $winnerName,
        };
    }

    private function createSettlementLog(
        Bet $bet,
        ?User $admin,
        string $settlementType,
        string $source,
        ?string $previousStatus,
        ?string $observation
    ): void {
        BetSettlementLog::query()->create([
            'bet_id' => $bet->id,
            'admin_id' => $admin?->id,
            'settlement_type' => $settlementType,
            'source' => $source,
            'previous_status' => $previousStatus,
            'new_status' => $settlementType,
            'observation' => $observation,
            'payload' => [
                'total_amount' => (string) $bet->total_amount,
                'total_odds' => (string) $bet->total_odds,
                'potential_win' => (string) $bet->potential_win,
            ],
        ]);
    }

    private function broadcastBet(Bet $bet, ?string $message = null): void
    {
        event(new BetStatusUpdated(
            userId: $bet->user_id,
            betId: $bet->id,
            code: $bet->code,
            status: $bet->status,
            totalAmount: (string) $bet->total_amount,
            potentialWin: (string) $bet->potential_win,
            message: $message
        ));
    }
}
