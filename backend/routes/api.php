<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\SimulationController;
use App\Http\Controllers\Api\TaxYearController;
use Illuminate\Support\Facades\Route;

// The two endpoints that hand out a session, held to the tighter `auth` limiter rather than the
// group's general one: five attempts a minute per account, twenty per address.
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:auth');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:auth');
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');

// whereNumber is not decoration: the controller type hints `int $year`, so a request for
// /api/tax-years/abc used to reach it and die on a TypeError — a 500 where the honest answer is
// that no such year exists. The constraint makes the router answer 404 before that.
Route::get('/tax-years/{year}', [TaxYearController::class, 'show'])->whereNumber('year');
Route::get('/tax-years/{year}/municipalities', [TaxYearController::class, 'municipalities'])
    ->whereNumber('year');

Route::post('/simulations', [SimulationController::class, 'store']);
Route::get('/simulations/{token}', [SimulationController::class, 'show']);

Route::middleware('auth:sanctum')->prefix('me')->group(function () {
    Route::get('/simulations', [SimulationController::class, 'index']);
    Route::post('/simulations/{token}/claim', [SimulationController::class, 'claim']);
    Route::delete('/simulations/{id}', [SimulationController::class, 'destroy']);
});
