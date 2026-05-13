<?php

use App\Modules\Odds\Controllers\SportController;
use App\Modules\Odds\Controllers\SportEventController;
use Illuminate\Support\Facades\Route;

Route::prefix('sports')->group(function () {
    Route::get('/active', [SportController::class, 'active']);
});

Route::prefix('events')->group(function () {
    Route::get('/', [SportEventController::class, 'index']);
});

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
        });
    });
