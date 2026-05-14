<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Requests\UpdateSystemSettingsRequest;
use App\Modules\Admin\Resources\SystemSettingResource;
use App\Modules\Admin\Services\SystemSettingService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SystemSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SystemSettingService $systemSettingService
    ) {
    }

    #[OA\Get(
        path: '/admin/settings',
        summary: 'Listar configuraciones del sistema',
        description: 'Permite al administrador consultar región, mercado y frecuencia de sincronización configurados.',
        security: [['sanctum' => []]],
        tags: ['Admin Settings'],
        responses: [
            new OA\Response(response: 200, description: 'Configuraciones obtenidas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(): JsonResponse
    {
        return $this->successResponse(
            SystemSettingResource::collection($this->systemSettingService->all()),
            'Configuraciones obtenidas correctamente.'
        );
    }

    #[OA\Put(
        path: '/admin/settings',
        summary: 'Actualizar configuración de cuotas',
        description: 'Permite al administrador cambiar región por defecto, mercado por defecto e intervalo de sincronización.',
        security: [['sanctum' => []]],
        tags: ['Admin Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'odds.default_region',
                        type: 'string',
                        enum: ['us', 'uk', 'eu', 'au'],
                        example: 'au'
                    ),
                    new OA\Property(
                        property: 'odds.default_market',
                        type: 'string',
                        enum: ['h2h', 'spreads', 'totals', 'outrights'],
                        example: 'h2h'
                    ),
                    new OA\Property(
                        property: 'odds.sync_interval_seconds',
                        type: 'integer',
                        minimum: 15,
                        maximum: 3600,
                        example: 120
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Configuraciones actualizadas correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function update(UpdateSystemSettingsRequest $request): JsonResponse
    {
        return $this->successResponse(
            SystemSettingResource::collection(
                $this->systemSettingService->updateMany($request->validated())
            ),
            'Configuraciones actualizadas correctamente.'
        );
    }
}
