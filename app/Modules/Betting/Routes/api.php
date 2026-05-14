<?php

use App\Modules\Betting\Controllers\BetController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'account.active', 'role:customer'])
    ->prefix('bets')
    ->group(function () {
        Route::post('/quote', [BetController::class, 'quote']);
        Route::get('/', [BetController::class, 'index']);
        Route::post('/', [BetController::class, 'store']);
        Route::get('/{bet}', [BetController::class, 'show']);
        Route::post('/{bet}/cancel', [BetController::class, 'cancel']);
    });
