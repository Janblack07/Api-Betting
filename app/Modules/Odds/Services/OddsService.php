<?php

namespace App\Modules\Odds\Services;

use App\Modules\Odds\Models\OddsSnapshot;
use App\Modules\Odds\Models\SportEvent;
use Illuminate\Support\Collection;

class OddsService
{
    public function getGroupedOddsForEvent(SportEvent $event): array
    {
        $odds = OddsSnapshot::query()
            ->where('sport_event_id', $event->id)
            ->where('is_active', true)
            ->orderBy('market_key')
            ->orderBy('bookmaker_title')
            ->orderBy('selection_name')
            ->get();

        return $odds
            ->groupBy('market_key')
            ->map(function (Collection $marketOdds, string $marketKey) {
                return [
                    'market_key' => $marketKey,
                    'bookmakers' => $marketOdds
                        ->groupBy('bookmaker_key')
                        ->map(function (Collection $bookmakerOdds, string $bookmakerKey) {
                            $first = $bookmakerOdds->first();

                            return [
                                'bookmaker_key' => $bookmakerKey,
                                'bookmaker_title' => $first?->bookmaker_title,
                                'outcomes' => $bookmakerOdds->map(fn (OddsSnapshot $odd) => [
                                    'snapshot_id' => $odd->id,
                                    'selection_name' => $odd->selection_name,
                                    'selection_description' => $odd->selection_description,
                                    'price' => $odd->price,
                                    'point' => $odd->point,
                                    'snapshot_at' => $odd->snapshot_at?->toISOString(),
                                    'is_active' => $odd->is_active,
                                    'available_for_betting' => $odd->is_active === true,
                                ])->values(),
                            ];
                        })
                        ->values(),
                ];
            })
            ->values()
            ->toArray();
    }
}
