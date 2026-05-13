<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ApiUsageLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class ApiUsageService
{
    public function history(array $filters = []): LengthAwarePaginator
    {
        return ApiUsageLog::query()
            ->when($filters['provider'] ?? null, fn ($query, string $provider) => $query->where('provider', $provider))
            ->when($filters['sport_key'] ?? null, fn ($query, string $sportKey) => $query->where('sport_key', $sportKey))
            ->when($filters['date_from'] ?? null, fn ($query, string $dateFrom) => $query->whereDate('requested_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($query, string $dateTo) => $query->whereDate('requested_at', '<=', $dateTo))
            ->latest('requested_at')
            ->paginate((int) ($filters['per_page'] ?? 15));
    }

    public function monthlySummary(?string $month = null): array
    {
        $date = $month
            ? Carbon::createFromFormat('Y-m', $month)->startOfMonth()
            : now()->startOfMonth();

        $from = $date->copy()->startOfMonth();
        $to = $date->copy()->endOfMonth();

        $query = ApiUsageLog::query()
            ->whereBetween('requested_at', [$from, $to]);

        $totalCredits = (clone $query)->sum('credits_used');
        $totalRequests = (clone $query)->count();
        $lastLog = (clone $query)->latest('requested_at')->first();

        $byEndpoint = ApiUsageLog::query()
            ->selectRaw('endpoint, COUNT(*) as total_calls, SUM(credits_used) as total_credits')
            ->whereBetween('requested_at', [$from, $to])
            ->groupBy('endpoint')
            ->orderByDesc('total_credits')
            ->get();

        $bySport = ApiUsageLog::query()
            ->selectRaw('sport_key, COUNT(*) as total_calls, SUM(credits_used) as total_credits')
            ->whereBetween('requested_at', [$from, $to])
            ->whereNotNull('sport_key')
            ->groupBy('sport_key')
            ->orderByDesc('total_credits')
            ->get();

        return [
            'period' => [
                'month' => $from->format('Y-m'),
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
            'totals' => [
                'total_requests' => $totalRequests,
                'total_credits_used' => (int) $totalCredits,
                'last_requests_used' => $lastLog?->requests_used,
                'last_requests_remaining' => $lastLog?->requests_remaining,
            ],
            'by_endpoint' => $byEndpoint,
            'by_sport' => $bySport,
        ];
    }
}
