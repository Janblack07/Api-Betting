<?php

namespace App\Modules\Wallet\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Shared\Traits\ApiResponse;
use App\Modules\Wallet\Models\WalletTransaction;
use App\Modules\Wallet\Resources\WalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class WalletTransactionController extends Controller
{
    use ApiResponse;

    #[OA\Get(
        path: '/wallet/transactions',
        summary: 'Listar movimientos de wallet',
        description: 'Devuelve movimientos de wallet del usuario autenticado.',
        security: [['sanctum' => []]],
        tags: ['Wallet'],
        parameters: [
            new OA\Parameter(
                name: 'type',
                description: 'Tipo de transacción',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'deposit'
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
            new OA\Response(response: 200, description: 'Movimientos obtenidos correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->getAuthIdentifier();

        $transactions = WalletTransaction::query()
            ->where('user_id', $userId)
            ->when(
                $request->query('type'),
                fn ($query, string $type) => $query->where('type', $type)
            )
            ->latest()
            ->paginate((int) $request->query('per_page', 15));

        return $this->successResponse(
            WalletTransactionResource::collection($transactions)->response()->getData(true),
            'Movimientos obtenidos correctamente.'
        );
    }
}
