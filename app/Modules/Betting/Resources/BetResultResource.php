<?php

namespace App\Modules\Betting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'bet_id' => $this->id,
            'code' => $this->code,
            'type' => $this->type,

            'status' => $this->status,
            'status_label' => $this->statusLabel(),

            'total_amount' => $this->total_amount,
            'total_odds' => $this->total_odds,
            'potential_win' => $this->potential_win,

            'settled_at' => $this->settled_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),

            'result_summary' => [
                'is_pending' => in_array($this->status, ['pending', 'accepted'], true),
                'is_won' => $this->status === 'won',
                'is_lost' => $this->status === 'lost',
                'is_refunded' => $this->status === 'refunded',
                'is_cancelled' => $this->status === 'cancelled',
            ],

            'selections' => $this->selections->map(function ($selection) {
                return [
                    'id' => $selection->id,
                    'sport_event_id' => $selection->sport_event_id,
                    'selection_name' => $selection->selection_name,
                    'market_key' => $selection->market_key,
                    'bookmaker_key' => $selection->bookmaker_key,
                    'odds_price' => $selection->odds_price,
                    'status' => $selection->status,
                    'result' => $selection->result,

                    'event' => $selection->sportEvent ? [
                        'id' => $selection->sportEvent->id,
                        'home_team' => $selection->sportEvent->home_team,
                        'away_team' => $selection->sportEvent->away_team,
                        'status' => $selection->sportEvent->status,
                    ] : null,

                    'event_result' => $selection->sportEvent?->result ? [
                        'home_score' => $selection->sportEvent->result->home_score,
                        'away_score' => $selection->sportEvent->result->away_score,
                        'winner_name' => $selection->sportEvent->result->winner_name,
                        'result_type' => $selection->sportEvent->result->result_type,
                        'status' => $selection->sportEvent->result->status,
                        'source' => $selection->sportEvent->result->source,
                        'resulted_at' => $selection->sportEvent->result->resulted_at?->toISOString(),
                    ] : null,
                ];
            })->values(),
        ];
    }

    private function statusLabel(): string
    {
        return match ($this->status) {
            'pending' => 'Pendiente',
            'accepted' => 'Aceptada / En juego',
            'won' => 'Ganada',
            'lost' => 'Perdida',
            'refunded' => 'Reembolsada',
            'cancelled' => 'Cancelada',
            'rejected' => 'Rechazada',
            default => 'Desconocida',
        };
    }
}
