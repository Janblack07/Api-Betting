<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Requests\UpdateSystemSettingsRequest;
use App\Modules\Admin\Resources\SystemSettingResource;
use App\Modules\Admin\Services\AuditService;
use App\Modules\Admin\Services\SystemSettingService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class SystemSettingController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SystemSettingService $systemSettingService,
        private readonly AuditService $auditService
    ) {
    }

    #[OA\Get(
        path: '/admin/settings',
        summary: 'Listar configuraciones del sistema',
        description: 'Permite al administrador consultar región, mercado, montos de apuesta y frecuencia de sincronización configurados.',
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
        summary: 'Actualizar configuración del sistema',
        description: 'Permite al administrador cambiar montos mínimos/máximos de apuesta, región por defecto, mercado por defecto e intervalo de sincronización.',
        security: [['sanctum' => []]],
        tags: ['Admin Settings'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'betting.min_amount',
                        type: 'number',
                        format: 'float',
                        minimum: 0.01,
                        example: 1
                    ),
                    new OA\Property(
                        property: 'betting.max_amount',
                        type: 'number',
                        format: 'float',
                        minimum: 0.01,
                        example: 500
                    ),
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
        $oldValues = $this->systemSettingService->all()
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $settings = $this->systemSettingService->updateMany($request->validated());

        $newValues = $settings
            ->pluck('setting_value', 'setting_key')
            ->toArray();

        $this->auditService->log(
            module: 'admin',
            action: 'settings.updated',
            user: $request->user(),
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: [
                'updated_keys' => array_keys($request->validated()),
            ],
            request: $request
        );

        return $this->successResponse(
            SystemSettingResource::collection($settings),
            'Configuraciones actualizadas correctamente.'
        );
    }
}
