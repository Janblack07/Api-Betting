<?php

use Illuminate\Support\Facades\Route;

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
