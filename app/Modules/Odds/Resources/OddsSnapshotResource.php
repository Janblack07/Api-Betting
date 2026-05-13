<?php

namespace App\Modules\Odds\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OddsSnapshotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sport_event_id' => $this->sport_event_id,
            'external_event_id' => $this->external_event_id,
            'sport_key' => $this->sport_key,
            'bookmaker_key' => $this->bookmaker_key,
            'bookmaker_title' => $this->bookmaker_title,
            'market_key' => $this->market_key,
            'selection_name' => $this->selection_name,
            'selection_description' => $this->selection_description,
            'price' => $this->price,
            'point' => $this->point,
            'snapshot_at' => $this->snapshot_at?->toISOString(),
            'is_active' => $this->is_active,
            'available_for_betting' => $this->is_active === true,
        ];
    }
}
