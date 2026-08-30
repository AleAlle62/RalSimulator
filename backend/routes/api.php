<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimulationController;
use App\Http\Controllers\Api\TaxYearController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

Route::get('/tax-years/{year}', [TaxYearController::class, 'show']);
Route::get('/tax-years/{year}/municipalities', [TaxYearController::class, 'municipalities']);

Route::post('/simulations', [SimulationController::class, 'store']);
Route::get('/simulations/{token}', [SimulationController::class, 'show']);

Route::middleware('auth:sanctum')->prefix('me')->group(function () {
    Route::get('/simulations', [SimulationController::class, 'index']);
    Route::delete('/simulations/{id}', [SimulationController::class, 'destroy']);
});
