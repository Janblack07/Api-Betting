<?php

use App\Modules\Odds\Controllers\OddsController;
use App\Modules\Odds\Controllers\SportController;
use App\Modules\Odds\Controllers\SportEventController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Endpoints para usuarios autenticados
|--------------------------------------------------------------------------
| customer, operator y admin pueden ver deportes, eventos y cuotas.
*/
Route::middleware(['auth:sanctum', 'role:admin,operator,customer'])
    ->group(function () {
        Route::prefix('sports')->group(function () {
            Route::get('/active', [SportController::class, 'active']);
        });

        Route::prefix('events')->group(function () {
            Route::get('/', [SportEventController::class, 'index']);
            Route::get('/{sportEvent}', [SportEventController::class, 'show']);
            Route::get('/{sportEvent}/odds', [OddsController::class, 'eventOdds']);
        });
    });

/*
|--------------------------------------------------------------------------
| Endpoints exclusivos de administrador
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::prefix('sports')->group(function () {
            Route::get('/', [SportController::class, 'adminIndex']);
            Route::post('/sync', [SportController::class, 'sync']);
            Route::patch('/{sport}/status', [SportController::class, 'toggleStatus']);
        });

        Route::prefix('events')->group(function () {
            Route::post('/sync', [SportEventController::class, 'sync']);
            Route::post('/statuses/sync', [SportEventController::class, 'updateStatuses']);
        });

        Route::prefix('odds')->group(function () {
            Route::post('/sync', [OddsController::class, 'sync']);
        });
    });
