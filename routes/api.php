<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ConfigController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    
    // Transacciones
    Route::get('/dashboard/summary', [TransactionController::class, 'getSummary']);
    Route::get('/dashboard/trend', [TransactionController::class, 'getYearlyTrend']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions', [TransactionController::class, 'store']);
    Route::put('/transactions/{id}', [TransactionController::class, 'update']);
    Route::delete('/transactions/{id}', [TransactionController::class, 'destroy']);
    
    // Configuración (Catálogos)
    Route::get('/categories', [ConfigController::class, 'getCategories']);
    Route::get('/payment-methods', [ConfigController::class, 'getPaymentMethods']);

    // Administración de Usuarios (Solo Admin)
    Route::middleware('can:admin-only')->group(function () {
        Route::get('/admin/users', [AuthController::class, 'listUsers']);
        Route::post('/admin/users', [AuthController::class, 'register']);
    });
});
