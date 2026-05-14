<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncSportEventsAction;
use App\Modules\Odds\Actions\UpdateSportEventStatusesAction;
use App\Modules\Odds\Models\SportEvent;
use App\Modules\Odds\Requests\SportEventsQueryRequest;
use App\Modules\Odds\Requests\SyncSportEventsRequest;
use App\Modules\Odds\Requests\UpdateEventStatusesRequest;
use App\Modules\Odds\Resources\SportEventResource;
use App\Modules\Odds\Services\SportEventService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;
use Throwable;

class SportEventController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SportEventService $sportEventService
    ) {
    }

    #[OA\Get(
        path: '/events',
        summary: 'Listar eventos deportivos disponibles',
        description: 'Lista eventos activos y disponibles para usuarios autenticados.',
        security: [['sanctum' => []]],
        tags: ['Events'],
        parameters: [
            new OA\Parameter(
                name: 'sport_key',
                description: 'Clave del deporte',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_australia_aleague'
            ),
            new OA\Parameter(
                name: 'status',
                description: 'Estado del evento',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', enum: ['scheduled', 'live']),
                example: 'scheduled'
            ),
            new OA\Parameter(
                name: 'date_from',
                description: 'Fecha inicial',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string', format: 'date'),
                example: '2026-05-01'
            ),
            new OA\Parameter(
                name: 'date_to',
                description: 'Fecha final',
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
            new OA\Response(response: 200, description: 'Eventos deportivos obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function index(SportEventsQueryRequest $request): JsonResponse
    {
        $events = $this->sportEventService->getAvailableEventsBySport(
            $request->validated()
        );

        return $this->successResponse(
            SportEventResource::collection($events)->response()->getData(true),
            'Eventos deportivos obtenidos correctamente.'
        );
    }

    #[OA\Get(
        path: '/events/{sportEvent}',
        summary: 'Ver detalle de un evento deportivo',
        description: 'Muestra el detalle de un evento, estado, etiqueta en vivo/cerrado y cuotas disponibles.',
        security: [['sanctum' => []]],
        tags: ['Events'],
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
            new OA\Response(response: 200, description: 'Detalle del evento obtenido correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Evento no encontrado'),
        ]
    )]
    public function show(SportEvent $sportEvent): JsonResponse
    {
        $event = $this->sportEventService->getEventDetail($sportEvent);

        return $this->successResponse(
            new SportEventResource($event),
            'Detalle del evento obtenido correctamente.'
        );
    }

    #[OA\Post(
        path: '/admin/events/sync',
        summary: 'Sincronizar eventos deportivos',
        description: 'Consulta eventos desde The Odds API por deporte activo y los guarda o actualiza.',
        security: [['sanctum' => []]],
        tags: ['Admin Events'],
        parameters: [
            new OA\Parameter(
                name: 'sport_key',
                description: 'Sincronizar eventos de un deporte específico',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_australia_aleague'
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Eventos deportivos sincronizados correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 500, description: 'Error al sincronizar eventos'),
        ]
    )]
    public function sync(
        SyncSportEventsRequest $request,
        SyncSportEventsAction $action
    ): JsonResponse {
        try {
            $summary = $action->execute($request->validated('sport_key'));

            return $this->successResponse(
                $summary,
                'Eventos deportivos sincronizados correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar los eventos deportivos.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }

    #[OA\Post(
        path: '/admin/events/statuses/sync',
        summary: 'Actualizar estados de eventos deportivos',
        description: 'Actualiza automáticamente estados de eventos: scheduled, live, completed o cancelled según datos disponibles.',
        security: [['sanctum' => []]],
        tags: ['Admin Events'],
        parameters: [
            new OA\Parameter(
                name: 'sport_key',
                description: 'Actualizar estados de un deporte específico',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer_australia_aleague'
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Estados de eventos actualizados correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 500, description: 'Error al actualizar estados'),
        ]
    )]
    public function updateStatuses(
        UpdateEventStatusesRequest $request,
        UpdateSportEventStatusesAction $action
    ): JsonResponse {
        try {
            $summary = $action->execute($request->validated('sport_key'));

            return $this->successResponse(
                $summary,
                'Estados de eventos actualizados correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron actualizar los estados de eventos.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }
}
