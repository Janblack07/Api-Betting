<?php

namespace App\Modules\Admin\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiUsageLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider' => $this->provider,
            'endpoint' => $this->endpoint,
            'sport_key' => $this->sport_key,
            'regions' => $this->regions,
            'markets' => $this->markets,
            'credits_used' => $this->credits_used,
            'requests_used' => $this->requests_used,
            'requests_remaining' => $this->requests_remaining,
            'response_status' => $this->response_status,
            'requested_at' => $this->requested_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
