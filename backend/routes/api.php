<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\PerfController;

Route::get('/health', [HealthController::class, 'index']);
Route::get('/health/full', [HealthController::class, 'full']);

Route::prefix('api')->group(function () {
    Route::get('/ping', fn() => response()->json(['success' => true, 'message' => 'pong', 'time' => now()->toIso8601String()]));
    Route::get('/db-check', [HealthController::class, 'dbCheck']);
    Route::get('/db-health', [HealthController::class, 'dbHealth']);
    Route::get('/redis-check', [HealthController::class, 'redisCheck']);
    Route::get('/socket-check', [HealthController::class, 'socketCheck']);
    Route::get('/perf/metrics', [PerfController::class, 'metrics']);

    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:api');

    Route::get('/wallet/balance', [WalletController::class, 'balance'])->middleware('auth:api');
    Route::post('/wallet/credit', [WalletController::class, 'credit'])->middleware('auth:api');
    Route::post('/wallet/debit', [WalletController::class, 'debit'])->middleware('auth:api');

    Route::get('/slot/config', [SlotController::class, 'config']);
    Route::post('/slot/spin', [SlotController::class, 'spin'])->middleware('auth:api');
});
