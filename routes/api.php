<?php

use App\Http\Controllers\Api\V1\ExchangeRateController;
use App\Http\Middleware\EnsureExternalDataApiEnabled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::prefix('v1/exchange-rates')
    ->name('api.v1.exchange-rates.')
    ->middleware([EnsureExternalDataApiEnabled::class, 'throttle:60,1'])
    ->group(function (): void {
        Route::get('latest', [ExchangeRateController::class, 'latest'])->name('latest');
        Route::get('history', [ExchangeRateController::class, 'history'])->name('history');
    });
