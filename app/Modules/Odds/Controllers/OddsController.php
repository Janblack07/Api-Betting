<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncOddsAction;
use App\Modules\Odds\Models\SportEvent;
use App\Modules\Odds\Requests\SyncOddsRequest;
use App\Modules\Odds\Services\OddsService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class OddsController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly OddsService $oddsService
    ) {
    }

    public function eventOdds(SportEvent $sportEvent): JsonResponse
    {
        return $this->successResponse(
            [
                'event_id' => $sportEvent->id,
                'external_event_id' => $sportEvent->external_event_id,
                'sport_key' => $sportEvent->sport_key,
                'markets' => $this->oddsService->getGroupedOddsForEvent($sportEvent),
            ],
            'Cuotas disponibles obtenidas correctamente.'
        );
    }

    public function sync(SyncOddsRequest $request, SyncOddsAction $action): JsonResponse
    {
        try {
            $summary = $action->execute($request->validated());

            return $this->successResponse(
                $summary,
                'Cuotas sincronizadas correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar las cuotas.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }
}
