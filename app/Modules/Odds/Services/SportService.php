<?php

namespace App\Modules\Odds\Services;

use App\Modules\Odds\Models\Sport;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class SportService
{
    public function getActiveSports(): Collection
    {
        return Sport::query()
            ->where('active', true)
            ->orderBy('group')
            ->orderBy('title')
            ->get();
    }

    public function getAdminSports(array $filters = []): LengthAwarePaginator
    {
        return Sport::query()
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('sport_key', 'like', "%{$search}%")
                        ->orWhere('group', 'like', "%{$search}%");
                });
            })
            ->when(array_key_exists('active', $filters), function ($query) use ($filters) {
                $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
            })
            ->orderBy('group')
            ->orderBy('title')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function toggleStatus(Sport $sport, bool $active): Sport
    {
        $sport->update([
            'active' => $active,
        ]);

        return $sport->refresh();
    }
}
