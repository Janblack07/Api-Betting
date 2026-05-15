<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\AuditLog;
use App\Modules\Admin\Requests\AuditLogQueryRequest;
use App\Modules\Admin\Resources\AuditLogResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AuditLogController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/admin/audit-logs',
        summary: 'Listar logs de auditoría',
        description: 'HU-50: Lista auditoría de operaciones críticas del sistema.',
        security: [['sanctum' => []]],
        tags: ['Admin Audit Logs'],
        parameters: [
            new OA\Parameter(
                name: 'module',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'wallet'
            ),
            new OA\Parameter(
                name: 'action',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'wallet.bet_hold'
            ),
            new OA\Parameter(
                name: 'user_id',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 4
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
            new OA\Response(response: 200, description: 'Logs de auditoría obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(AuditLogQueryRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $logs = AuditLog::query()
            ->with('user')
            ->when($filters['module'] ?? null, fn ($query, string $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn ($query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn ($query, string $dateFrom) => $query->whereDate('performed_at', '>=', $dateFrom))
            ->when($filters['date_to'] ?? null, fn ($query, string $dateTo) => $query->whereDate('performed_at', '<=', $dateTo))
            ->latest('performed_at')
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse(
            AuditLogResource::collection($logs)->response()->getData(true),
            'Logs de auditoría obtenidos correctamente.'
        );
    }
}
