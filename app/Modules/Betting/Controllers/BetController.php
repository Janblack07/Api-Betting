<?php

namespace App\Modules\Betting\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Betting\Events\BetRejected;
use App\Modules\Betting\Models\Bet;
use App\Modules\Betting\Requests\BetHistoryRequest;
use App\Modules\Betting\Requests\QuoteBetRequest;
use App\Modules\Betting\Requests\StoreBetRequest;
use App\Modules\Betting\Resources\BetResource;
use App\Modules\Betting\Services\BetService;
use App\Modules\Shared\Traits\ApiResponse;
use App\Modules\Betting\Resources\BetResultResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;


class BetController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly BetService $betService
    ) {
    }

    #[OA\Post(
        path: '/bets/quote',
        summary: 'Calcular ganancia potencial',
        description: 'HU-31 y HU-32: Calcula la ganancia potencial usando cuotas vigentes del backend y detecta cambios de cuota.',
        security: [['sanctum' => []]],
        tags: ['Bets'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'selections'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 10),
                    new OA\Property(
                        property: 'selections',
                        type: 'array',
                        items: new OA\Items(
                            required: ['snapshot_id'],
                            properties: [
                                new OA\Property(property: 'snapshot_id', type: 'integer', example: 1),
                                new OA\Property(property: 'expected_price', type: 'number', example: 1.85),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: 'Cotización calculada correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function quote(QuoteBetRequest $request): JsonResponse
    {
        return $this->successResponse(
            $this->betService->quote($request->user(), $request->validated()),
            'Cotización calculada correctamente.'
        );
    }

    #[OA\Post(
        path: '/bets',
        summary: 'Crear apuesta',
        description: 'HU-30, HU-32, HU-33, HU-37 y HU-38: Crea apuesta simple o combinada usando snapshots vigentes del backend.',
        security: [['sanctum' => []]],
        tags: ['Bets'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['amount', 'selections'],
                properties: [
                    new OA\Property(property: 'amount', type: 'number', example: 10),
                    new OA\Property(property: 'accept_odds_change', type: 'boolean', example: false),
                    new OA\Property(
                        property: 'selections',
                        type: 'array',
                        items: new OA\Items(
                            required: ['snapshot_id'],
                            properties: [
                                new OA\Property(property: 'snapshot_id', type: 'integer', example: 1),
                                new OA\Property(property: 'expected_price', type: 'number', example: 1.85),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Apuesta creada correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'Error de validación'),
        ]
    )]
    public function store(StoreBetRequest $request): JsonResponse
{
    try {
        $bet = $this->betService->create(
            $request->user(),
            $request->validated()
        );

        return $this->successResponse(
            new BetResource($bet),
            'Apuesta creada correctamente.',
            201
        );
    } catch (ValidationException $exception) {
        $errors = $exception->errors();

        $reason = $this->resolveRejectionReason($errors);

        event(new BetRejected(
            userId: (int) $request->user()->getAuthIdentifier(),
            reason: $reason,
            message: $this->resolveRejectionMessage($reason),
            errors: $errors
        ));

        throw $exception;
    }
}
private function resolveRejectionReason(array $errors): string
{
    $flatErrors = collect($errors)
        ->flatten()
        ->implode(' ');

    $flatErrors = mb_strtolower($flatErrors);

    if (str_contains($flatErrors, 'saldo insuficiente')) {
        return 'insufficient_balance';
    }

    if (str_contains($flatErrors, 'evento está cerrado') || str_contains($flatErrors, 'no disponible')) {
        return 'event_closed';
    }

    if (str_contains($flatErrors, 'cuota') || str_contains($flatErrors, 'snapshot')) {
        return 'odds_unavailable';
    }

    if (str_contains($flatErrors, 'bloqueado') || str_contains($flatErrors, 'inactivo')) {
        return 'user_blocked';
    }

    if (str_contains($flatErrors, 'monto mínimo') || str_contains($flatErrors, 'monto máximo')) {
        return 'invalid_amount';
    }

    return 'validation_error';
}

private function resolveRejectionMessage(string $reason): string
{
    return match ($reason) {
        'insufficient_balance' => 'Saldo insuficiente para realizar la apuesta.',
        'event_closed' => 'El evento está cerrado o no disponible para apostar.',
        'odds_unavailable' => 'La cuota seleccionada ya no está disponible o cambió.',
        'user_blocked' => 'Tu cuenta no está habilitada para realizar apuestas.',
        'invalid_amount' => 'El monto de la apuesta no cumple los límites configurados.',
        default => 'La apuesta fue rechazada por validaciones del sistema.',
    };
}

    #[OA\Get(
        path: '/bets',
        summary: 'Consultar historial de apuestas',
        description: 'HU-34: Devuelve historial paginado de apuestas del usuario autenticado.',
        security: [['sanctum' => []]],
        tags: ['Bets'],
        parameters: [
            new OA\Parameter(
                name: 'status',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string'),
                example: 'accepted'
            ),
            new OA\Parameter(
                name: 'per_page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer'),
                example: 15
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Historial de apuestas obtenido correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
        ]
    )]
    public function index(BetHistoryRequest $request): JsonResponse
    {
        $bets = $this->betService->history($request->user(), $request->validated());

        return $this->successResponse(
            BetResource::collection($bets)->response()->getData(true),
            'Historial de apuestas obtenido correctamente.'
        );
    }

    #[OA\Get(
        path: '/bets/{bet}',
        summary: 'Ver detalle de una apuesta',
        description: 'HU-35: Muestra detalle completo de apuesta, selecciones, snapshot usado y transacciones asociadas.',
        security: [['sanctum' => []]],
        tags: ['Bets'],
        parameters: [
            new OA\Parameter(
                name: 'bet',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Detalle de apuesta obtenido correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 404, description: 'Apuesta no encontrada'),
        ]
    )]
    public function show(Request $request, Bet $bet): JsonResponse
    {
        $bet = $this->betService->findUserBet($request->user(), $bet->id);

        return $this->successResponse(
            new BetResource($bet),
            'Detalle de apuesta obtenido correctamente.'
        );
    }

    #[OA\Post(
        path: '/bets/{bet}/cancel',
        summary: 'Cancelar apuesta pendiente',
        description: 'HU-36: Cancela una apuesta permitida y devuelve el saldo bloqueado.',
        security: [['sanctum' => []]],
        tags: ['Bets'],
        parameters: [
            new OA\Parameter(
                name: 'bet',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Apuesta cancelada correctamente'),
            new OA\Response(response: 401, description: 'No autenticado'),
            new OA\Response(response: 403, description: 'No autorizado'),
            new OA\Response(response: 422, description: 'No se puede cancelar'),
        ]
    )]
    public function cancel(Request $request, Bet $bet): JsonResponse
    {
        $bet = $this->betService->cancel($request->user(), $bet);

        return $this->successResponse(
            new BetResource($bet),
            'Apuesta cancelada correctamente.'
        );
    }
    #[OA\Get(
    path: '/bets/{bet}/result',
    summary: 'Consultar resultado de una apuesta',
    description: 'Permite al usuario consultar si una apuesta ganó, perdió, fue reembolsada o sigue pendiente.',
    security: [['sanctum' => []]],
    tags: ['Bets'],
    parameters: [
        new OA\Parameter(
            name: 'bet',
            description: 'ID de la apuesta',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer'),
            example: 6
        ),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Resultado de apuesta obtenido correctamente'),
        new OA\Response(response: 401, description: 'No autenticado'),
        new OA\Response(response: 403, description: 'No autorizado'),
        new OA\Response(response: 404, description: 'Apuesta no encontrada'),
    ]
)]
public function result(Request $request, Bet $bet): JsonResponse
{
    $bet = $this->betService->findUserBetResult(
        user: $request->user(),
        betId: $bet->id
    );

    return $this->successResponse(
        new BetResultResource($bet),
        'Resultado de apuesta obtenido correctamente.'
    );
}
}
