<?php

use App\Modules\Auth\Http\Controllers\AdminUserAccountController;
use App\Modules\Auth\Http\Controllers\AuthController;
use App\Modules\Auth\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register']);
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware(['auth:sanctum', 'account.active'])->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('user/profile', [ProfileController::class, 'show']);
    });

    Route::middleware(['auth:sanctum', 'account.active', 'role:admin'])->group(function (): void {
        Route::patch('admin/users/{user}/account', [AdminUserAccountController::class, 'update']);
    });
require base_path('app/Modules/Odds/Routes/api.php');

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente.',
        'data' => [
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString(),
        ],
    ]);
});
require base_path('app/Modules/Admin/Routes/api.php');

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'API funcionando correctamente.',
        'data' => [
            'app' => config('app.name'),
            'environment' => app()->environment(),
            'timestamp' => now()->toISOString(),
        ],
    ]);
});

});
