<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncSportsAction;
use App\Modules\Odds\Models\Sport;
use App\Modules\Odds\Requests\ToggleSportStatusRequest;
use App\Modules\Odds\Resources\SportResource;
use App\Modules\Odds\Services\SportService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;
use Throwable;

class SportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SportService $sportService
    ) {
    }

    #[OA\Get(
        path: '/sports/active',
        summary: 'Listar deportes activos',
        description: 'Devuelve los deportes activos disponibles para usuarios autenticados.',
        security: [['sanctum' => []]],
        tags: ['Sports'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deportes activos obtenidos correctamente'
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function active(): JsonResponse
    {
        $sports = $this->sportService->getActiveSports();

        return $this->successResponse(
            SportResource::collection($sports),
            'Deportes activos obtenidos correctamente.'
        );
    }

    #[OA\Get(
        path: '/admin/sports',
        summary: 'Listar deportes para administración',
        description: 'Devuelve deportes paginados para administración, con filtros opcionales.',
        security: [['sanctum' => []]],
        tags: ['Admin Sports'],
        parameters: [
            new OA\Parameter(
                name: 'search',
                description: 'Buscar por título, sport_key o grupo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'soccer'
            ),
            new OA\Parameter(
                name: 'active',
                description: 'Filtrar por estado activo/inactivo',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'boolean'),
                example: true
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
            new OA\Response(response: 200, description: 'Deportes obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function adminIndex(Request $request): JsonResponse
    {
        $sports = $this->sportService->getAdminSports($request->only([
            'search',
            'active',
            'per_page',
        ]));

        return $this->successResponse(
            SportResource::collection($sports)->response()->getData(true),
            'Deportes obtenidos correctamente.'
        );
    }

    #[OA\Post(
        path: '/admin/sports/sync',
        summary: 'Sincronizar deportes desde The Odds API',
        description: 'Consulta The Odds API y guarda o actualiza deportes sin duplicarlos.',
        security: [['sanctum' => []]],
        tags: ['Admin Sports'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Deportes sincronizados correctamente'
            ),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 500, description: 'Error al sincronizar deportes'),
        ]
    )]
    public function sync(SyncSportsAction $action): JsonResponse
    {
        try {
            $summary = $action->execute();

            return $this->successResponse(
                $summary,
                'Deportes sincronizados correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar los deportes.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }

    #[OA\Patch(
        path: '/admin/sports/{sport}/status',
        summary: 'Activar o desactivar deporte',
        description: 'Permite a un administrador activar o desactivar un deporte.',
        security: [['sanctum' => []]],
        tags: ['Admin Sports'],
        parameters: [
            new OA\Parameter(
                name: 'sport',
                description: 'ID del deporte',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['active'],
                properties: [
                    new OA\Property(property: 'active', type: 'boolean', example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado del deporte actualizado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function toggleStatus(
        ToggleSportStatusRequest $request,
        Sport $sport
    ): JsonResponse {
        $sport = $this->sportService->toggleStatus(
            $sport,
            $request->boolean('active')
        );

        return $this->successResponse(
            new SportResource($sport),
            $sport->active
                ? 'Deporte activado correctamente.'
                : 'Deporte desactivado correctamente.'
        );
    }
}
