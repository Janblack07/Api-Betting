<?php

namespace App\Modules\Betting\Services;

use App\Models\User;
use App\Modules\Admin\Events\AdminDashboardUpdated;
use App\Modules\Admin\Services\SystemSettingService;
use App\Modules\Betting\Events\BetStatusUpdated;
use App\Modules\Betting\Models\Bet;
use App\Modules\Odds\Models\OddsSnapshot;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BetService
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly SystemSettingService $systemSettingService
    ) {
    }

    public function quote(User $user, array $data): array
    {
        $amount = (float) $data['amount'];

        $this->validateAmountLimits($amount);

        $resolved = $this->resolveSelections(
            selections: $data['selections'],
            acceptOddsChange: false
        );

        if ($resolved['odds_changed']) {
            return [
                'can_create' => false,
                'reason' => 'odds_changed',
                'message' => 'Una o más cuotas cambiaron. Debes confirmar el nuevo valor.',
                'amount' => number_format($amount, 2, '.', ''),
                'old_total_odds' => $resolved['old_total_odds'],
                'new_total_odds' => number_format($resolved['total_odds'], 4, '.', ''),
                'new_potential_win' => number_format($amount * $resolved['total_odds'], 2, '.', ''),
                'changes' => $resolved['changes'],
            ];
        }

        return [
            'can_create' => true,
            'type' => count($resolved['snapshots']) > 1 ? 'combo' : 'single',
            'amount' => number_format($amount, 2, '.', ''),
            'total_odds' => number_format($resolved['total_odds'], 4, '.', ''),
            'potential_win' => number_format($amount * $resolved['total_odds'], 2, '.', ''),
            'selections' => $resolved['preview'],
        ];
    }

    public function create(User $user, array $data): Bet
    {
        $amount = (float) $data['amount'];
        $acceptOddsChange = (bool) ($data['accept_odds_change'] ?? false);

        $this->validateAmountLimits($amount);

        $resolved = $this->resolveSelections(
            selections: $data['selections'],
            acceptOddsChange: $acceptOddsChange
        );

        if ($resolved['odds_changed'] && ! $acceptOddsChange) {
            throw ValidationException::withMessages([
                'odds' => [
                    'Una o más cuotas cambiaron. Confirma el nuevo valor antes de apostar.',
                ],
                'changes' => $resolved['changes'],
            ]);
        }

        $bet = DB::transaction(function () use ($user, $amount, $resolved) {
            $type = count($resolved['snapshots']) > 1 ? 'combo' : 'single';
            $totalOdds = $resolved['total_odds'];
            $potentialWin = $amount * $totalOdds;

            $bet = Bet::query()->create([
                'user_id' => $user->id,
                'code' => $this->generateCode(),
                'type' => $type,
                'total_amount' => $amount,
                'total_odds' => $totalOdds,
                'potential_win' => $potentialWin,
                'status' => 'pending',
                'placed_at' => now(),
            ]);

            foreach ($resolved['snapshots'] as $snapshot) {
                $bet->selections()->create([
                    'sport_event_id' => $snapshot->sport_event_id,
                    'snapshot_id' => $snapshot->id,
                    'external_event_id' => $snapshot->external_event_id,
                    'sport_key' => $snapshot->sport_key,
                    'market_key' => $snapshot->market_key,
                    'bookmaker_key' => $snapshot->bookmaker_key,
                    'selection_name' => $snapshot->selection_name,
                    'odds_price' => $snapshot->price,
                    'point' => $snapshot->point,
                    'status' => 'pending',
                    'result' => null,
                ]);
            }

            $this->walletService->holdBetAmount(
                user: $user,
                amount: $amount,
                betId: $bet->id
            );

            $bet->update([
                'status' => 'accepted',
            ]);

            return $bet->load([
                'selections.snapshot',
                'selections.sportEvent',
                'walletTransactions',
            ]);
        });

        $this->broadcastBet($bet, 'Apuesta creada correctamente.');

        event(new AdminDashboardUpdated('bet.created', [
            'bet_id' => $bet->id,
            'code' => $bet->code,
            'user_id' => $bet->user_id,
            'type' => $bet->type,
            'amount' => (string) $bet->total_amount,
            'total_odds' => (string) $bet->total_odds,
            'potential_win' => (string) $bet->potential_win,
            'status' => $bet->status,
        ]));

        return $bet;
    }

    public function history(User $user, array $filters): LengthAwarePaginator
    {
        return Bet::query()
            ->where('user_id', $user->id)
            ->with('selections')
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            )
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function findUserBet(User $user, int $betId): Bet
    {
        return Bet::query()
            ->where('user_id', $user->id)
            ->with([
                'selections.snapshot',
                'selections.sportEvent',
                'walletTransactions',
            ])
            ->findOrFail($betId);
    }

    public function cancel(User $user, Bet $bet): Bet
    {
        if ((int) $bet->user_id !== (int) $user->id) {
            abort(403, 'No puedes cancelar una apuesta que no te pertenece.');
        }

        if (! in_array($bet->status, ['pending', 'accepted'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Solo se pueden cancelar apuestas pendientes o aceptadas.'],
            ]);
        }

        $bet = DB::transaction(function () use ($user, $bet) {
            $bet = Bet::query()
                ->where('id', $bet->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->walletService->refundBet(
                user: $user,
                stakeAmount: (float) $bet->total_amount,
                betId: $bet->id
            );

            $bet->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $bet->selections()->update([
                'status' => 'cancelled',
                'result' => 'cancelled',
            ]);

            return $bet->load([
                'selections.snapshot',
                'selections.sportEvent',
                'walletTransactions',
            ]);
        });

        $this->broadcastBet($bet, 'Apuesta cancelada correctamente.');

        event(new AdminDashboardUpdated('bet.cancelled', [
            'bet_id' => $bet->id,
            'code' => $bet->code,
            'user_id' => $bet->user_id,
            'amount' => (string) $bet->total_amount,
            'status' => $bet->status,
        ]));

        return $bet;
    }

    private function resolveSelections(array $selections, bool $acceptOddsChange): array
    {
        $snapshots = [];
        $preview = [];
        $changes = [];
        $totalOdds = 1;
        $oldTotalOdds = 1;
        $eventIds = [];

        foreach ($selections as $selection) {
            $selectedSnapshot = OddsSnapshot::query()
                ->with('sportEvent')
                ->findOrFail($selection['snapshot_id']);

            $this->validateSnapshotEvent($selectedSnapshot);

            if (in_array((int) $selectedSnapshot->sport_event_id, $eventIds, true)) {
                throw ValidationException::withMessages([
                    'selections' => [
                        'No puedes incluir más de una selección del mismo evento en una apuesta combinada.',
                    ],
                ]);
            }

            $eventIds[] = (int) $selectedSnapshot->sport_event_id;

            $expectedPrice = isset($selection['expected_price'])
                ? (float) $selection['expected_price']
                : (float) $selectedSnapshot->price;

            $latestSnapshot = $this->latestActiveEquivalentSnapshot($selectedSnapshot);

            if (! $latestSnapshot) {
                throw ValidationException::withMessages([
                    'snapshot_id' => ['La cuota seleccionada ya no está disponible.'],
                ]);
            }

            $priceChanged = (float) $latestSnapshot->price !== $expectedPrice
                || (int) $latestSnapshot->id !== (int) $selectedSnapshot->id;

            if ($priceChanged) {
                $changes[] = [
                    'old_snapshot_id' => $selectedSnapshot->id,
                    'new_snapshot_id' => $latestSnapshot->id,
                    'market_key' => $latestSnapshot->market_key,
                    'selection_name' => $latestSnapshot->selection_name,
                    'old_price' => number_format($expectedPrice, 4, '.', ''),
                    'new_price' => number_format((float) $latestSnapshot->price, 4, '.', ''),
                ];

                if (! $acceptOddsChange) {
                    $oldTotalOdds *= $expectedPrice;
                    $totalOdds *= (float) $latestSnapshot->price;

                    continue;
                }
            }

            $snapshots[] = $latestSnapshot;
            $oldTotalOdds *= $expectedPrice;
            $totalOdds *= (float) $latestSnapshot->price;

            $preview[] = [
                'snapshot_id' => $latestSnapshot->id,
                'sport_event_id' => $latestSnapshot->sport_event_id,
                'external_event_id' => $latestSnapshot->external_event_id,
                'sport_key' => $latestSnapshot->sport_key,
                'market_key' => $latestSnapshot->market_key,
                'bookmaker_key' => $latestSnapshot->bookmaker_key,
                'bookmaker_title' => $latestSnapshot->bookmaker_title,
                'selection_name' => $latestSnapshot->selection_name,
                'price' => number_format((float) $latestSnapshot->price, 4, '.', ''),
                'point' => $latestSnapshot->point,
            ];
        }

        if (! empty($changes) && ! $acceptOddsChange) {
            return [
                'odds_changed' => true,
                'changes' => $changes,
                'snapshots' => [],
                'preview' => [],
                'old_total_odds' => number_format($oldTotalOdds, 4, '.', ''),
                'total_odds' => $totalOdds,
            ];
        }

        return [
            'odds_changed' => ! empty($changes),
            'changes' => $changes,
            'snapshots' => $snapshots,
            'preview' => $preview,
            'old_total_odds' => number_format($oldTotalOdds, 4, '.', ''),
            'total_odds' => $totalOdds,
        ];
    }

    private function validateSnapshotEvent(OddsSnapshot $snapshot): void
    {
        $event = $snapshot->sportEvent;

        if (! $event) {
            throw ValidationException::withMessages([
                'event' => ['El evento asociado a la cuota no existe.'],
            ]);
        }

        if (! $event->isAvailableForBetting()) {
            throw ValidationException::withMessages([
                'event' => ['El evento está cerrado o no disponible para apostar.'],
            ]);
        }
    }

    private function latestActiveEquivalentSnapshot(OddsSnapshot $snapshot): ?OddsSnapshot
    {
        return OddsSnapshot::query()
            ->with('sportEvent')
            ->where('sport_event_id', $snapshot->sport_event_id)
            ->where('bookmaker_key', $snapshot->bookmaker_key)
            ->where('market_key', $snapshot->market_key)
            ->where('selection_name', $snapshot->selection_name)
            ->where(function ($query) use ($snapshot) {
                is_null($snapshot->point)
                    ? $query->whereNull('point')
                    : $query->where('point', $snapshot->point);
            })
            ->where('is_active', true)
            ->latest('snapshot_at')
            ->first();
    }

    private function validateAmountLimits(float $amount): void
    {
        $min = (float) $this->systemSettingService->get('betting.min_amount', 1);
        $max = (float) $this->systemSettingService->get('betting.max_amount', 500);

        if ($amount < $min) {
            throw ValidationException::withMessages([
                'amount' => ["El monto mínimo para apostar es {$min}."],
            ]);
        }

        if ($amount > $max) {
            throw ValidationException::withMessages([
                'amount' => ["El monto máximo para apostar es {$max}."],
            ]);
        }
    }

    private function generateCode(): string
    {
        do {
            $code = 'BET-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
        } while (Bet::query()->where('code', $code)->exists());

        return $code;
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
