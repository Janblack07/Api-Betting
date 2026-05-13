<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Auth\Contracts\UserModerationServiceInterface;
use App\Modules\Auth\Http\Requests\ModerateUserAccountRequest;
use App\Modules\Auth\Http\Resources\AuthenticatedUserResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Administración', description: 'HU-05 Moderación de cuentas')]
class AdminUserAccountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly UserModerationServiceInterface $userModerationService,
    ) {}

    #[OA\Patch(
        path: '/admin/users/{user}/account',
        operationId: 'adminModerateUserAccount',
        description: 'HU-05: Bloquear, suspender o reactivar usuario. Motivo obligatorio al bloquear/suspender. Revoca tokens si aplica.',
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['blocked', 'suspended', 'active']),
                    new OA\Property(property: 'reason', type: 'string', nullable: true),
                ]
            )
        ),
        tags: ['Administración'],
        parameters: [
            new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuario actualizado'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Sin permisos'),
            new OA\Response(response: 422, description: 'Validación'),
        ]
    )]
    public function update(ModerateUserAccountRequest $request, User $user): JsonResponse
    {
        $this->authorize('moderate', $user);

        $updated = $this->userModerationService->setAccountStatus(
            $request->user(),
            $user,
            $request->validated('status'),
            $request->validated('reason')
        );

        $updated->load(['roles', 'wallet']);

        return $this->successResponse(
            new AuthenticatedUserResource($updated),
            'Estado de cuenta actualizado.'
        );
    }
}
