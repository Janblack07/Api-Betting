<?php

namespace App\Modules\Betting\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BetSelectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bet_id' => $this->bet_id,
            'sport_event_id' => $this->sport_event_id,
            'snapshot_id' => $this->snapshot_id,
            'external_event_id' => $this->external_event_id,
            'sport_key' => $this->sport_key,
            'market_key' => $this->market_key,
            'bookmaker_key' => $this->bookmaker_key,
            'selection_name' => $this->selection_name,
            'odds_price' => $this->odds_price,
            'point' => $this->point,
            'status' => $this->status,
            'result' => $this->result,
            'snapshot' => $this->whenLoaded('snapshot'),
            'sport_event' => $this->whenLoaded('sportEvent'),
        ];
    }
}
