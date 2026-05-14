<?php

namespace App\Modules\Betting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Betting\Models\Bet;
use App\Modules\Betting\Requests\ManualSettleBetRequest;
use App\Modules\Betting\Resources\BetResource;
use App\Modules\Betting\Services\BetSettlementService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

class BetSettlementController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BetSettlementService $settlementService
    ) {
    }

    #[OA\Post(
        path: '/admin/bets/{bet}/settle',
        summary: 'Liquidar apuesta manualmente',
        description: 'HU-43: Permite al administrador liquidar una apuesta como ganada, perdida o reembolsada.',
        security: [['sanctum' => []]],
        tags: ['Admin Bet Settlement'],
        parameters: [
            new OA\Parameter(
                name: 'bet',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['result', 'observation'],
                properties: [
                    new OA\Property(
                        property: 'result',
                        type: 'string',
                        enum: ['won', 'lost', 'refunded'],
                        example: 'won'
                    ),
                    new OA\Property(
                        property: 'observation',
                        type: 'string',
                        example: 'Liquidación manual por revisión administrativa.'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Apuesta liquidada correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function manual(ManualSettleBetRequest $request, Bet $bet): JsonResponse
    {
        $bet = $this->settlementService->manualSettle(
            bet: $bet,
            result: $request->string('result')->toString(),
            observation: $request->string('observation')->toString(),
            admin: $request->user()
        );

        return $this->successResponse(
            new BetResource($bet),
            'Apuesta liquidada correctamente.'
        );
    }
}
