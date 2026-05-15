<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Requests\ApiUsageReportRequest;
use App\Modules\Admin\Requests\BetReportRequest;
use App\Modules\Admin\Requests\WalletTransactionReportRequest;
use App\Modules\Betting\Models\Bet;
use App\Modules\Betting\Resources\AdminBetResource;
use App\Modules\Shared\Traits\ApiResponse;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Resources\WalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use OpenApi\Attributes as OA;

class AdminReportController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/admin/reports/bets',
        summary: 'Reporte de apuestas por fecha',
        description: 'HU-57: Genera reporte de apuestas por rango de fechas, con totales apostados, ganados y perdidos.',
        security: [['sanctum' => []]],
        tags: ['Admin Reports'],
        parameters: [
            new OA\Parameter(
                name: 'date_from',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-01'
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-31'
            ),
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'lost'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte de apuestas generado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function bets(BetReportRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $baseQuery = Bet::query()
            ->whereDate('created_at', '>=', $filters['date_from'])
            ->whereDate('created_at', '<=', $filters['date_to'])
            ->when(
                $filters['status'] ?? null,
                fn ($query, string $status) => $query->where('status', $status)
            );

        $totalAmountBet = (float) (clone $baseQuery)->sum('total_amount');

        $totalWonByUsers = (float) (clone $baseQuery)
            ->where('status', 'won')
            ->sum('potential_win');

        $totalLostByUsers = (float) (clone $baseQuery)
            ->where('status', 'lost')
            ->sum('total_amount');

        $totalRefunded = (float) (clone $baseQuery)
            ->where('status', 'refunded')
            ->sum('total_amount');

        $countByStatus = (clone $baseQuery)
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $bets = (clone $baseQuery)
            ->with(['user', 'selections'])
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse([
            'filters' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'status' => $filters['status'] ?? null,
            ],
            'summary' => [
                'total_bets' => (clone $baseQuery)->count(),
                'total_amount_bet' => number_format($totalAmountBet, 2, '.', ''),
                'total_won_by_users' => number_format($totalWonByUsers, 2, '.', ''),
                'total_lost_by_users' => number_format($totalLostByUsers, 2, '.', ''),
                'total_refunded' => number_format($totalRefunded, 2, '.', ''),
                'count_by_status' => $countByStatus,
            ],
            'bets' => AdminBetResource::collection($bets)->response()->getData(true),
        ], 'Reporte de apuestas generado correctamente.');
    }

    #[OA\Get(
        path: '/admin/reports/wallet-transactions',
        summary: 'Reporte de movimientos de wallet',
        description: 'HU-58: Lista movimientos de wallet paginados con filtros por usuario, tipo y fecha.',
        security: [['sanctum' => []]],
        tags: ['Admin Reports'],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 4
            ),
            new OA\Parameter(
                name: 'type',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'bet_hold'
            ),
            new OA\Parameter(
                name: 'date_from',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-01'
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-31'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte de movimientos de wallet generado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function walletTransactions(WalletTransactionReportRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $query = WalletTransaction::query()
            ->with(['user', 'wallet'])
            ->when(
                $filters['user_id'] ?? null,
                fn ($query, int $userId) => $query->where('user_id', $userId)
            )
            ->when(
                $filters['type'] ?? null,
                fn ($query, string $type) => $query->where('type', $type)
            )
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo)
            );

        $totalAmount = (float) (clone $query)->sum('amount');

        $amountByType = (clone $query)
            ->select('type', DB::raw('SUM(amount) as total_amount'), DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get()
            ->map(fn ($row) => [
                'type' => $row->type,
                'total_amount' => number_format((float) $row->total_amount, 2, '.', ''),
                'total' => (int) $row->total,
            ]);

        $transactions = (clone $query)
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse([
            'filters' => [
                'user_id' => $filters['user_id'] ?? null,
                'type' => $filters['type'] ?? null,
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
            ],
            'summary' => [
                'total_transactions' => (clone $query)->count(),
                'total_amount' => number_format($totalAmount, 2, '.', ''),
                'amount_by_type' => $amountByType,
            ],
            'transactions' => WalletTransactionResource::collection($transactions)->response()->getData(true),
        ], 'Reporte de movimientos de wallet generado correctamente.');
    }

    #[OA\Get(
        path: '/admin/reports/api-usage',
        summary: 'Reporte de consumo de API externa',
        description: 'HU-59: Reporte de consumo de The Odds API con llamadas por día, créditos usados, endpoints más consumidos y errores.',
        security: [['sanctum' => []]],
        tags: ['Admin Reports'],
        parameters: [
            new OA\Parameter(
                name: 'date_from',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-01'
            ),
            new OA\Parameter(
                name: 'date_to',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-31'
            ),
            new OA\Parameter(
                name: 'endpoint',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: '/sports/soccer_epl/odds'
            ),
            new OA\Parameter(
                name: 'sport_key',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_epl'
            ),
            new OA\Parameter(
                name: 'status_code',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 200
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Reporte de consumo de API generado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function apiUsage(ApiUsageReportRequest $request): JsonResponse
    {
        if (! Schema::hasTable('api_usage_logs')) {
            return $this->successResponse([
                'summary' => [
                    'total_requests' => 0,
                    'total_credits_used' => 0,
                    'calls_by_day' => [],
                    'top_endpoints' => [],
                    'errors' => [],
                ],
                'logs' => [
                    'data' => [],
                    'meta' => [
                        'total' => 0,
                    ],
                ],
            ], 'Reporte de consumo de API generado correctamente.');
        }

        $filters = $request->validated();

        $query = DB::table('api_usage_logs')
            ->when(
                $filters['date_from'] ?? null,
                fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom)
            )
            ->when(
                $filters['date_to'] ?? null,
                fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo)
            )
            ->when(
                $filters['endpoint'] ?? null,
                fn ($query, string $endpoint) => $query->where('endpoint', 'like', "%{$endpoint}%")
            )
            ->when(
                $filters['sport_key'] ?? null,
                fn ($query, string $sportKey) => $query->where('sport_key', $sportKey)
            )
            ->when(
                $filters['status_code'] ?? null,
                fn ($query, int $statusCode) => $query->where('status_code', $statusCode)
            );

        $totalRequests = (clone $query)->count();
        $totalCreditsUsed = (int) (clone $query)->sum('credits_used');

        $callsByDay = (clone $query)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total_calls, SUM(credits_used) as credits_used')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('day')
            ->get()
            ->map(fn ($row) => [
                'day' => $row->day,
                'total_calls' => (int) $row->total_calls,
                'credits_used' => (int) $row->credits_used,
            ]);

        $topEndpoints = (clone $query)
            ->select('endpoint', DB::raw('COUNT(*) as total_calls'), DB::raw('SUM(credits_used) as credits_used'))
            ->groupBy('endpoint')
            ->orderByDesc('total_calls')
            ->limit(10)
            ->get()
            ->map(fn ($row) => [
                'endpoint' => $row->endpoint,
                'total_calls' => (int) $row->total_calls,
                'credits_used' => (int) $row->credits_used,
            ]);

        $errors = (clone $query)
            ->where(function ($query) {
                $query->whereNull('status_code')
                    ->orWhere('status_code', '>=', 400);
            })
            ->select('endpoint', 'sport_key', 'status_code', 'error_message', 'created_at')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $logs = (clone $query)
            ->orderByDesc('created_at')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse([
            'filters' => [
                'date_from' => $filters['date_from'] ?? null,
                'date_to' => $filters['date_to'] ?? null,
                'endpoint' => $filters['endpoint'] ?? null,
                'sport_key' => $filters['sport_key'] ?? null,
                'status_code' => $filters['status_code'] ?? null,
            ],
            'summary' => [
                'total_requests' => $totalRequests,
                'total_credits_used' => $totalCreditsUsed,
                'calls_by_day' => $callsByDay,
                'top_endpoints' => $topEndpoints,
                'errors' => $errors,
            ],
            'logs' => $logs,
        ], 'Reporte de consumo de API generado correctamente.');
    }
}
