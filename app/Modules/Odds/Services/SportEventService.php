<?php

namespace App\Modules\Odds\Services;

use App\Modules\Odds\Models\SportEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SportEventService
{
    public function getAvailableEventsBySport(array $filters = []): LengthAwarePaginator
    {
        return SportEvent::query()
            ->with('sport')
            ->where('is_active', true)
            ->whereIn('status', ['scheduled', 'live'])
            ->when($filters['sport_key'] ?? null, function ($query, string $sportKey) {
                $query->where('sport_key', $sportKey);
            })
            ->when($filters['status'] ?? null, function ($query, string $status) {
                $query->where('status', $status);
            })
            ->when($filters['date_from'] ?? null, function ($query, string $dateFrom) {
                $query->where('commence_time', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function ($query, string $dateTo) {
                $query->where('commence_time', '<=', $dateTo);
            })
            ->orderBy('commence_time')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }
}
