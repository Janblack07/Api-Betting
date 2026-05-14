<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'No autenticado.',
        'errors' => null,
    ], 401);
})->name('login');
