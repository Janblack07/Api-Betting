<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Resources\ApiUsageLogResource;
use App\Modules\Admin\Services\ApiUsageService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class ApiUsageController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ApiUsageService $apiUsageService
    ) {
    }

    #[OA\Get(
        path: '/admin/api-usage',
        summary: 'Ver historial de consumo de The Odds API',
        description: 'HU-17: Permite al administrador consultar el historial de consumo de créditos, requests usados y requests restantes del proveedor The Odds API.',
        security: [['sanctum' => []]],
        tags: ['Admin API Usage'],
        parameters: [
            new OA\Parameter(
                name: 'provider',
                description: 'Proveedor externo. Por defecto: the_odds_api',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'the_odds_api'
            ),
            new OA\Parameter(
                name: 'sport_key',
                description: 'Filtrar consumo por deporte',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_australia_aleague'
            ),
            new OA\Parameter(
                name: 'date_from',
                description: 'Fecha inicial del historial',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-01'
            ),
            new OA\Parameter(
                name: 'date_to',
                description: 'Fecha final del historial',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-31'
            ),
            new OA\Parameter(
                name: 'per_page',
                description: 'Cantidad de registros por página',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Historial de consumo obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Historial de consumo obtenido correctamente.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'data',
                                    type: 'array',
                                    items: new OA\Items(
                                        type: 'object',
                                        properties: [
                                            new OA\Property(property: 'id', type: 'integer', example: 1),
                                            new OA\Property(property: 'provider', type: 'string', example: 'the_odds_api'),
                                            new OA\Property(property: 'endpoint', type: 'string', example: '/sports/soccer_australia_aleague/events'),
                                            new OA\Property(property: 'sport_key', type: 'string', example: 'soccer_australia_aleague'),
                                            new OA\Property(property: 'regions', type: 'string', example: 'au'),
                                            new OA\Property(property: 'markets', type: 'string', example: 'h2h'),
                                            new OA\Property(property: 'credits_used', type: 'integer', example: 1),
                                            new OA\Property(property: 'requests_used', type: 'integer', example: 20),
                                            new OA\Property(property: 'requests_remaining', type: 'integer', example: 480),
                                            new OA\Property(property: 'response_status', type: 'integer', example: 200),
                                            new OA\Property(property: 'requested_at', type: 'string', format: 'date-time'),
                                        ]
                                    )
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $logs = $this->apiUsageService->history($request->only([
            'provider',
            'sport_key',
            'date_from',
            'date_to',
            'per_page',
        ]));

        return $this->successResponse(
            ApiUsageLogResource::collection($logs)->response()->getData(true),
            'Historial de consumo obtenido correctamente.'
        );
    }

    #[OA\Get(
        path: '/admin/api-usage/summary',
        summary: 'Ver resumen mensual de consumo de The Odds API',
        description: 'HU-17: Devuelve resumen mensual de créditos usados, requests realizados, requests restantes y consumo agrupado por endpoint y deporte.',
        security: [['sanctum' => []]],
        tags: ['Admin API Usage'],
        parameters: [
            new OA\Parameter(
                name: 'month',
                description: 'Mes a consultar en formato YYYY-MM. Si no se envía, usa el mes actual.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: '2026-05'
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Resumen de consumo obtenido correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Resumen de consumo obtenido correctamente.'),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(
                                    property: 'period',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'month', type: 'string', example: '2026-05'),
                                        new OA\Property(property: 'from', type: 'string', example: '2026-05-01'),
                                        new OA\Property(property: 'to', type: 'string', example: '2026-05-31'),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'totals',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'total_requests', type: 'integer', example: 25),
                                        new OA\Property(property: 'total_credits_used', type: 'integer', example: 18),
                                        new OA\Property(property: 'last_requests_used', type: 'integer', example: 25),
                                        new OA\Property(property: 'last_requests_remaining', type: 'integer', example: 475),
                                    ]
                                ),
                                new OA\Property(
                                    property: 'by_endpoint',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                ),
                                new OA\Property(
                                    property: 'by_sport',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function summary(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->apiUsageService->monthlySummary($request->query('month')),
            'Resumen de consumo obtenido correctamente.'
        );
    }
}
