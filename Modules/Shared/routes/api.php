<?php

use Illuminate\Support\Facades\Route;
use Modules\Shared\Http\Controllers\CurrencyController;
use Modules\Shared\Http\Controllers\UtilityTypeController;
use Modules\Shared\Models\Currency;

Route::prefix('v1')->group(function () {
    Route::get('currencies', [CurrencyController::class, 'index'])->name('currencies.index');
    Route::get('currencies/{currency}', [CurrencyController::class, 'show'])->name('currencies.show');

    Route::middleware('auth:api')->group(function () {
        Route::post('currencies', [CurrencyController::class, 'store'])->name('currencies.store')->can('create', Currency::class);
        Route::put('currencies/{currency}', [CurrencyController::class, 'update'])->name('currencies.update');
        Route::delete('currencies/{currency}', [CurrencyController::class, 'destroy'])->name('currencies.destroy');
    });

    Route::apiResource('utility-types', UtilityTypeController::class)->only(['index', 'show']);
});
