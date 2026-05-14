<?php

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\Shared\Traits\ApiResponse;
use App\Modules\Wallet\Requests\ManualDepositRequest;
use App\Modules\Wallet\Requests\ManualWithdrawRequest;
use App\Modules\Wallet\Resources\WalletResource;
use App\Modules\Wallet\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WalletController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly WalletService $walletService
    ) {
    }

    #[OA\Get(
        path: '/wallet',
        summary: 'Consultar saldo de wallet',
        description: 'HU-25: Devuelve balance, locked_balance y available_balance del usuario autenticado.',
        security: [['sanctum' => []]],
        tags: ['Wallet'],
        responses: [
            new OA\Response(response: 200, description: 'Wallet obtenida correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getUserWallet($request->user());

        return $this->successResponse(
            new WalletResource($wallet),
            'Wallet obtenida correctamente.'
        );
    }

    #[OA\Post(
        path: '/admin/wallet/deposit',
        summary: 'Registrar depósito manual',
        description: 'HU-26: Permite al administrador aumentar saldo de un usuario.',
        security: [['sanctum' => []]],
        tags: ['Admin Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'amount'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 20.50),
                    new OA\Property(property: 'description', type: 'string', example: 'Depósito manual de prueba'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Depósito registrado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function manualDeposit(ManualDepositRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->integer('user_id'));

        $wallet = $this->walletService->deposit(
            user: $user,
            amount: (float) $request->input('amount'),
            description: $request->input('description'),
            referenceType: 'manual_deposit',
            referenceId: (int) $request->user()->getAuthIdentifier()
        );

        return $this->successResponse(
            new WalletResource($wallet),
            'Depósito registrado correctamente.'
        );
    }

    #[OA\Post(
        path: '/admin/wallet/withdraw',
        summary: 'Registrar retiro manual',
        description: 'HU-27: Permite al administrador descontar saldo disponible de un usuario.',
        security: [['sanctum' => []]],
        tags: ['Admin Wallet'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'amount'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer', example: 2),
                    new OA\Property(property: 'amount', type: 'number', format: 'float', example: 10.00),
                    new OA\Property(property: 'description', type: 'string', example: 'Retiro manual de prueba'),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Retiro registrado correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function manualWithdraw(ManualWithdrawRequest $request): JsonResponse
    {
        $user = User::query()->findOrFail($request->integer('user_id'));

        $wallet = $this->walletService->withdraw(
            user: $user,
            amount: (float) $request->input('amount'),
            description: $request->input('description'),
            referenceType: 'manual_withdraw',
            referenceId: (int) $request->user()->getAuthIdentifier()
        );

        return $this->successResponse(
            new WalletResource($wallet),
            'Retiro registrado correctamente.'
        );
    }
}
