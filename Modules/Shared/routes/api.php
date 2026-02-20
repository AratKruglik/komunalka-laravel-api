<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\CurrencyController;
use Modules\Shared\Http\Controllers\UtilityTypeController;

Route::prefix('v1')->group(function () {
    Route::apiResource('currencies', CurrencyController::class);
    Route::apiResource('utility-types', UtilityTypeController::class)->only(['index', 'show']);
});
