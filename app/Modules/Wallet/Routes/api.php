<?php

use App\Modules\Wallet\Controllers\WalletController;
use App\Modules\Wallet\Controllers\WalletTransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'account.active', 'role:admin,operator,customer'])
    ->prefix('wallet')
    ->group(function () {
        Route::get('/', [WalletController::class, 'show']);
        Route::get('/transactions', [WalletTransactionController::class, 'index']);
    });

Route::middleware(['auth:sanctum', 'account.active', 'role:admin'])
    ->prefix('admin/wallet')
    ->group(function () {
        Route::post('/deposit', [WalletController::class, 'manualDeposit']);
        Route::post('/withdraw', [WalletController::class, 'manualWithdraw']);
    });
