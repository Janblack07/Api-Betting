<?php

namespace App\Modules\Betting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Betting\Actions\SyncEventResultsAction;
use App\Modules\Betting\Requests\StoreManualEventResultRequest;
use App\Modules\Betting\Requests\SyncEventResultsRequest;
use App\Modules\Betting\Services\BetSettlementService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class EventResultController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BetSettlementService $settlementService
    ) {
    }

    #[OA\Post(
        path: '/admin/results/manual',
        summary: 'Cargar resultado manual de evento',
        description: 'HU-39: Permite cargar manualmente el resultado de un evento y liquidar apuestas relacionadas.',
        security: [['sanctum' => []]],
        tags: ['Admin Results'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['sport_event_id', 'result_type'],
                properties: [
                    new OA\Property(
                        property: 'sport_event_id',
                        type: 'integer',
                        example: 1
                    ),
                    new OA\Property(
                        property: 'home_score',
                        type: 'integer',
                        nullable: true,
                        example: 2
                    ),
                    new OA\Property(
                        property: 'away_score',
                        type: 'integer',
                        nullable: true,
                        example: 1
                    ),
                    new OA\Property(
                        property: 'result_type',
                        type: 'string',
                        enum: ['home', 'away', 'draw', 'cancelled'],
                        example: 'home'
                    ),
                    new OA\Property(
                        property: 'winner_name',
                        type: 'string',
                        nullable: true,
                        example: 'Manchester City'
                    ),
                    new OA\Property(
                        property: 'observation',
                        type: 'string',
                        nullable: true,
                        example: 'Resultado cargado manualmente.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Resultado manual registrado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 500, description: 'Error interno del servidor'),
        ]
    )]
    public function manual(StoreManualEventResultRequest $request): JsonResponse
    {
        $result = $this->settlementService->storeManualEventResult(
            data: $request->validated(),
            admin: $request->user()
        );

        return $this->successResponse(
            $result,
            'Resultado manual registrado correctamente.'
        );
    }

    #[OA\Post(
        path: '/admin/results/sync',
        summary: 'Sincronizar resultados desde proveedor',
        description: 'HU-39: Consulta scores desde The Odds API, registra resultados de eventos completados y dispara la liquidación automática de apuestas relacionadas.',
        security: [['sanctum' => []]],
        tags: ['Admin Results'],
        parameters: [
            new OA\Parameter(
                name: 'sport_key',
                description: 'Clave del deporte a sincronizar',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_epl'
            ),
            new OA\Parameter(
                name: 'days_from',
                description: 'Días hacia atrás para consultar eventos completados. Máximo 3.',
                in: 'query',
                required: false,
                schema: new OA\Schema(
                    type: 'integer',
                    minimum: 1,
                    maximum: 3
                ),
                example: 3
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Resultados sincronizados correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
            new OA\Response(response: 500, description: 'Error al sincronizar resultados'),
        ]
    )]
    public function sync(
        SyncEventResultsRequest $request,
        SyncEventResultsAction $action
    ): JsonResponse {
        $summary = $action->execute(
            sportKey: $request->validated('sport_key'),
            daysFrom: (int) ($request->validated('days_from') ?? 3)
        );

        return $this->successResponse(
            $summary,
            'Resultados sincronizados correctamente.'
        );
    }
}
