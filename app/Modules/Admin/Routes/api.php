<?php

use App\Modules\Admin\Controllers\AdminDashboardController;
use App\Modules\Admin\Controllers\AdminUserController;
use App\Modules\Admin\Controllers\ApiUsageController;
use App\Modules\Admin\Controllers\AuditLogController;
use App\Modules\Admin\Controllers\SystemSettingController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'account.active', 'role:admin'])
    ->prefix('admin')
    ->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);
        Route::get('/audit-logs', [AuditLogController::class, 'index']);

        Route::prefix('api-usage')->group(function () {
            Route::get('/', [ApiUsageController::class, 'index']);
            Route::get('/summary', [ApiUsageController::class, 'summary']);
        });

        Route::prefix('settings')->group(function () {
            Route::get('/', [SystemSettingController::class, 'index']);
            Route::put('/', [SystemSettingController::class, 'update']);
        });

        Route::prefix('users')->group(function () {
            Route::get('/', [AdminUserController::class, 'index']);
            Route::patch('/{user}/status', [AdminUserController::class, 'updateStatus']);
        });
    });
