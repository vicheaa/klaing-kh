<?php

use App\Http\Controllers\Api\V1\AdministrativeAreaController;
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

Route::prefix('v1/administrative-areas')
    ->name('api.v1.administrative-areas.')
    ->middleware('throttle:60,1,administrative-areas')
    ->group(function (): void {
        Route::get('/', [AdministrativeAreaController::class, 'index'])->name('index');
        Route::get('{area}', [AdministrativeAreaController::class, 'show'])
            ->where('area', '[0-9]{2}|[0-9]{4}|[0-9]{6}|[a-f0-9]{64}')
            ->name('show');
    });
