<?php

namespace App\Modules\Auth\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Contracts\AuthServiceInterface;
use App\Modules\Auth\Exceptions\InactiveAccountException;
use App\Modules\Auth\Http\Requests\LoginRequest;
use App\Modules\Auth\Http\Requests\RegisterRequest;
use App\Modules\Auth\Http\Resources\AuthenticatedUserResource;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Autenticación', description: 'HU-01, HU-02, HU-03')]
class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly AuthServiceInterface $authService,
    ) {}

    #[OA\Post(
        path: '/auth/register',
        operationId: 'authRegister',
        description: 'HU-01: Registro con rol customer y wallet USD en saldo 0. Devuelve token Sanctum.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'name', type: 'string', maxLength: 150),
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                    new OA\Property(property: 'password_confirmation', type: 'string', format: 'password'),
                ]
            )
        ),
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Usuario creado',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(
                            property: 'data',
                            properties: [
                                new OA\Property(property: 'token', type: 'string'),
                                new OA\Property(property: 'token_type', type: 'string', example: 'Bearer'),
                                new OA\Property(property: 'user', type: 'object'),
                            ],
                            type: 'object'
                        ),
                    ]
                )
            ),
            new OA\Response(response: 422, description: 'Errores de validación'),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new AuthenticatedUserResource($result['user']),
        ], 'Registro completado.', 201);
    }

    #[OA\Post(
        path: '/auth/login',
        operationId: 'authLogin',
        description: 'HU-02: Inicio de sesión. Usuarios bloqueados o suspendidos reciben 403.',
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(property: 'email', type: 'string', format: 'email'),
                    new OA\Property(property: 'password', type: 'string', format: 'password'),
                ]
            )
        ),
        tags: ['Autenticación'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Credenciales válidas',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string'),
                        new OA\Property(property: 'data', type: 'object'),
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Credenciales incorrectas'),
            new OA\Response(response: 403, description: 'Cuenta bloqueada o suspendida'),
            new OA\Response(response: 422, description: 'Validación'),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login($request->validated());
        } catch (InactiveAccountException $e) {
            return $this->errorResponse($e->getMessage(), null, 403);
        }

        if ($result === null) {
            return $this->errorResponse('Credenciales incorrectas.', null, 401);
        }

        return $this->successResponse([
            'token' => $result['token'],
            'token_type' => 'Bearer',
            'user' => new AuthenticatedUserResource($result['user']),
        ], 'Sesión iniciada correctamente.');
    }

    #[OA\Post(
        path: '/auth/logout',
        operationId: 'authLogout',
        description: 'HU-03: Revoca el token actual.',
        security: [['sanctum' => []]],
        tags: ['Autenticación'],
        responses: [
            new OA\Response(response: 200, description: 'Sesión cerrada'),
            new OA\Response(response: 401, description: 'No autenticado'),
        ]
    )]
    public function logout(\Illuminate\Http\Request $request): JsonResponse
    {
        $this->authService->logout($request);

        return $this->successResponse(null, 'Sesión cerrada correctamente.');
    }
}
