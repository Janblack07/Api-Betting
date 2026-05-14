<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Admin\Requests\AdminUserQueryRequest;
use App\Modules\Admin\Requests\UpdateUserStatusRequest;
use App\Modules\Admin\Resources\AdminUserResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class AdminUserController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/admin/users',
        summary: 'Listar usuarios en administración',
        description: 'HU-49: Lista usuarios paginados con filtros por estado, rol y búsqueda.',
        security: [['sanctum' => []]],
        tags: ['Admin Users'],
        parameters: [
            new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'active'),
            new OA\Parameter(name: 'role', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'customer'),
            new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string'), example: 'cliente'),
            new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer'), example: 15),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Usuarios obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(AdminUserQueryRequest $request): JsonResponse
    {
        $filters = $request->validated();

        $users = User::query()
            ->with(['roles', 'wallet'])
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['role'] ?? null, function ($query, string $role) {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', $role));
            })
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate((int) ($filters['per_page'] ?? 15));

        return $this->successResponse(
            AdminUserResource::collection($users)->response()->getData(true),
            'Usuarios obtenidos correctamente.'
        );
    }

    #[OA\Patch(
        path: '/admin/users/{user}/status',
        summary: 'Bloquear o desbloquear usuario',
        description: 'HU-49: Permite cambiar el estado de un usuario desde administración.',
        security: [['sanctum' => []]],
        tags: ['Admin Users'],
        parameters: [
            new OA\Parameter(
                name: 'user',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 4
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['status'],
                properties: [
                    new OA\Property(property: 'status', type: 'string', enum: ['active', 'blocked', 'inactive'], example: 'blocked'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Estado del usuario actualizado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function updateStatus(UpdateUserStatusRequest $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id && $request->input('status') !== 'active') {
            return $this->errorResponse(
                'No puedes bloquear o desactivar tu propia cuenta.',
                null,
                422
            );
        }

        $user->update([
            'status' => $request->input('status'),
        ]);

        $user->load(['roles', 'wallet']);

        return $this->successResponse(
            new AdminUserResource($user),
            'Estado del usuario actualizado correctamente.'
        );
    }
}
