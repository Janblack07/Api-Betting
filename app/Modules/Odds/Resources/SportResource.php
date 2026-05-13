<?php

namespace App\Modules\Odds\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sport_key' => $this->sport_key,
            'group' => $this->group,
            'title' => $this->title,
            'description' => $this->description,
            'active' => $this->active,
            'has_outrights' => $this->has_outrights,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
