<?php

namespace App\Modules\Odds\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Odds\Actions\SyncSportsAction;
use App\Modules\Odds\Models\Sport;
use App\Modules\Odds\Requests\ToggleSportStatusRequest;
use App\Modules\Odds\Resources\SportResource;
use App\Modules\Odds\Services\SportService;
use App\Modules\Shared\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class SportController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly SportService $sportService
    ) {
    }

    public function active(): JsonResponse
    {
        $sports = $this->sportService->getActiveSports();

        return $this->successResponse(
            SportResource::collection($sports),
            'Deportes activos obtenidos correctamente.'
        );
    }

    public function adminIndex(Request $request): JsonResponse
    {
        $sports = $this->sportService->getAdminSports($request->only([
            'search',
            'active',
            'per_page',
        ]));

        return $this->successResponse(
            SportResource::collection($sports)->response()->getData(true),
            'Deportes obtenidos correctamente.'
        );
    }

    public function sync(SyncSportsAction $action): JsonResponse
    {
        try {
            $summary = $action->execute();

            return $this->successResponse(
                $summary,
                'Deportes sincronizados correctamente.'
            );
        } catch (Throwable $exception) {
            return $this->errorResponse(
                'No se pudieron sincronizar los deportes.',
                [
                    'detail' => $exception->getMessage(),
                ],
                500
            );
        }
    }

    public function toggleStatus(
        ToggleSportStatusRequest $request,
        Sport $sport
    ): JsonResponse {
        $sport = $this->sportService->toggleStatus(
            $sport,
            $request->boolean('active')
        );

        return $this->successResponse(
            new SportResource($sport),
            $sport->active
                ? 'Deporte activado correctamente.'
                : 'Deporte desactivado correctamente.'
        );
    }
}
