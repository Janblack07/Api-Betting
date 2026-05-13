<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Resources\UserProfileResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Usuario', description: 'HU-04 Perfil')]
class ProfileController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/user/profile',
        operationId: 'userProfile',
        description: 'HU-04: Datos del usuario autenticado (sin contraseña), rol, estado y wallet.',
        security: [['sanctum' => []]],
        tags: ['Usuario'],
        responses: [
            new OA\Response(response: 200, description: 'Perfil'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'Cuenta no disponible'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load(['roles', 'wallet']);

        return $this->successResponse(
            new UserProfileResource($user),
            'Perfil obtenido correctamente.'
        );
    }
}
