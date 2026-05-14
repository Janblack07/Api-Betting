<?php

use App\Modules\Betting\Controllers\BetController;
use App\Modules\Betting\Controllers\BetSettlementController;
use App\Modules\Betting\Controllers\EventResultController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'account.active', 'role:customer'])
    ->prefix('bets')
    ->group(function () {
        Route::post('/quote', [BetController::class, 'quote']);
        Route::get('/', [BetController::class, 'index']);
        Route::post('/', [BetController::class, 'store']);
        Route::get('/{bet}/result', [BetController::class, 'result']);
        Route::get('/{bet}', [BetController::class, 'show']);
        Route::post('/{bet}/cancel', [BetController::class, 'cancel']);
    });

Route::middleware(['auth:sanctum', 'account.active', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::post('/results/manual', [EventResultController::class, 'manual']);
        Route::post('/results/sync', [EventResultController::class, 'sync']);
        Route::post('/bets/{bet}/settle', [BetSettlementController::class, 'manual']);
    });
