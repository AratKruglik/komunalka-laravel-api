<?php

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\ServiceProviderController;

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::get('service-providers', [ServiceProviderController::class, 'index'])->name('service-providers.index');
    Route::get('service-providers/{id}', [ServiceProviderController::class, 'show'])->name('service-providers.show');
    Route::get('service-providers/address/{addressId}', [ServiceProviderController::class, 'byAddress'])->name('service-providers.by-address');
    Route::post('service-providers', [ServiceProviderController::class, 'store'])->name('service-providers.store');
    Route::put('service-providers/{id}', [ServiceProviderController::class, 'update'])->name('service-providers.update');
    Route::delete('service-providers/{id}', [ServiceProviderController::class, 'destroy'])->name('service-providers.destroy');
});
