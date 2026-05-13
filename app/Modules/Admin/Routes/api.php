<?php

use App\Modules\Admin\Controllers\ApiUsageController;
use App\Modules\Admin\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::prefix('api-usage')->group(function () {
            Route::get('/', [ApiUsageController::class, 'index']);
            Route::get('/summary', [ApiUsageController::class, 'summary']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index']);
            Route::put('/', [SystemSettingController::class, 'update']);
        });
    });
