<?php

namespace App\Modules\Odds\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SportEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'external_event_id' => $this->external_event_id,
            'sport_key' => $this->sport_key,
            'sport' => new SportResource($this->whenLoaded('sport')),
            'home_team' => $this->home_team,
            'away_team' => $this->away_team,
            'commence_time' => $this->commence_time?->toISOString(),
            'status' => $this->status,
            'is_live' => $this->is_live,
            'is_active' => $this->is_active,
            'available_for_betting' => $this->isAvailableForBetting(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
