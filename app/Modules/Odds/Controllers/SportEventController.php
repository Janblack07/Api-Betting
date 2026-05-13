<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncSportEventsAction;
use App\Modules\Odds\Requests\SportEventsQueryRequest;
use App\Modules\Odds\Requests\SyncSportEventsRequest;
use App\Modules\Odds\Resources\SportEventResource;
use App\Modules\Odds\Services\SportEventService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Throwable;

class SportEventController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SportEventService $sportEventService
    ) {
    }

    public function index(SportEventsQueryRequest $request): JsonResponse
    {
        $events = $this->sportEventService->getAvailableEventsBySport(
            $request->validated()
        );

        return $this->successResponse(
            SportEventResource::collection($events)->response()->getData(true),
            'Eventos deportivos obtenidos correctamente.'
        );
    }

    public function sync(
        SyncSportEventsRequest $request,
        SyncSportEventsAction $action
    ): JsonResponse {
        try {
            $summary = $action->execute($request->validated('sport_key'));

            return $this->successResponse(
                $summary,
                'Eventos deportivos sincronizados correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar los eventos deportivos.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }
}
