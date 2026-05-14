<?php

namespace App\Modules\Betting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Betting\Models\Bet;
use App\Modules\Betting\Requests\AdminBetQueryRequest;
use App\Modules\Betting\Resources\AdminBetResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class AdminBetController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/admin/bets',
        summary: 'Listar apuestas en administración',
        description: 'HU-45: Lista todas las apuestas con filtros por usuario, estado y fecha.',
        security: [['sanctum' => []]],
        tags: ['Admin Bets'],
        parameters: [
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 4),
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'accepted'),
            new OA\Parameter(name: 'date_from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-01'),
            new OA\Parameter(name: 'date_to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), example: '2026-05-31'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 15),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Apuestas obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(AdminBetQueryRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $bets = Bet::query()
            ->with(['user', 'selections'])
            ->when($filters['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['date_from'] ?? null, fn ($query, string $dateFrom) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($query, string $dateTo) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse(
            AdminBetResource::collection($bets)->response()->getData(true),
            'Apuestas obtenidas correctamente.'
        );
    }

    #[OA\Get(
        path: '/admin/bets/{bet}',
        summary: 'Ver detalle administrativo de apuesta',
        description: 'HU-46: Muestra usuario, selección, snapshot, movimientos de wallet y auditoría de liquidación.',
        security: [['sanctum' => []]],
        tags: ['Admin Bets'],
        parameters: [
            new OA\Parameter(
                name: 'bet',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 6
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle administrativo de apuesta obtenido correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Apuesta no encontrada'),
        ]
    )]
    public function show(Request $request, Bet $bet): JsonResponse
    {
        $bet->load([
            'user',
            'selections.snapshot',
            'selections.sportEvent.result',
            'walletTransactions',
            'settlementLogs',
        ]);

        return $this->successResponse(
            new AdminBetResource($bet),
            'Detalle administrativo de apuesta obtenido correctamente.'
        );
    }
}
