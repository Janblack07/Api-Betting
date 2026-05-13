<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncOddsAction;
use App\Modules\Odds\Models\SportEvent;
use App\Modules\Odds\Requests\SyncOddsRequest;
use App\Modules\Odds\Services\OddsService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;
use OpenApi\Attributes as OA;

class OddsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OddsService $oddsService
    ) {
    }
 #[OA\Get(
        path: '/events/{sportEvent}/odds',
        summary: 'Listar cuotas disponibles de un evento',
        description: 'HU-16: Devuelve cuotas activas agrupadas por mercado y bookmaker.',
        tags: ['Odds'],
        parameters: [
            new OA\Parameter(
                name: 'sportEvent',
                description: 'ID interno del evento deportivo',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuotas disponibles obtenidas correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cuotas disponibles obtenidas correctamente.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'event_id', type: 'integer', example: 1),
                                new OA\Property(property: 'external_event_id', type: 'string', example: 'abc123'),
                                new OA\Property(property: 'sport_key', type: 'string', example: 'soccer_epl'),
                                new OA\Property(
                                    property: 'markets',
                                    type: 'array',
                                    items: new OA\Items(type: 'object')
                                ),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
        ]
    )]
    public function eventOdds(SportEvent $sportEvent): JsonResponse
    {
        return $this->successResponse(
            [
                'event_id' => $sportEvent->id,
                'external_event_id' => $sportEvent->external_event_id,
                'sport_key' => $sportEvent->sport_key,
                'markets' => $this->oddsService->getGroupedOddsForEvent($sportEvent),
            ],
            'Cuotas disponibles obtenidas correctamente.'
        );
    }
    #[OA\Post(
        path: '/admin/odds/sync',
        summary: 'Sincronizar cuotas desde The Odds API',
        description: 'HU-13, HU-14 y HU-15: Consulta cuotas por deporte, región y mercado; guarda snapshots y detecta cambios por hash.',
        security: [['sanctum' => []]],
        tags: ['Admin Odds'],
        parameters: [
            new OA\Parameter(
                name: 'sport_key',
                description: 'Filtrar eventos por deporte',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_epl'
            ),
            new OA\Parameter(
                name: 'event_id',
                description: 'ID interno del evento específico',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
            new OA\Parameter(
                name: 'regions',
                description: 'Regiones de bookmakers',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'eu'
            ),
            new OA\Parameter(
                name: 'markets',
                description: 'Mercados de apuesta',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'h2h'
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Cantidad máxima de eventos a procesar',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 10
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Cuotas sincronizadas correctamente',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Cuotas sincronizadas correctamente.'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'events_processed', type: 'integer', example: 1),
                                new OA\Property(property: 'created', type: 'integer', example: 6),
                                new OA\Property(property: 'unchanged', type: 'integer', example: 2),
                                new OA\Property(property: 'deactivated', type: 'integer', example: 1),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 500, description: 'Error al sincronizar cuotas'),
        ]
    )]

    public function sync(SyncOddsRequest $request, SyncOddsAction $action): JsonResponse
    {
        try {
            $summary = $action->execute($request->validated());

            return $this->successResponse(
                $summary,
                'Cuotas sincronizadas correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar las cuotas.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }
}
