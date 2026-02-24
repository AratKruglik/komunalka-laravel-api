<?php

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\AddressController;
use Modules\Address\Http\Controllers\AddressTypeController;
use Modules\Address\Http\Controllers\RegionController;

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::get('address', [AddressController::class, 'index'])->name('address.index');
    Route::get('address/{id}', [AddressController::class, 'show'])->name('address.show');
    Route::post('address', [AddressController::class, 'store'])->name('address.store');
    Route::put('address/{id}', [AddressController::class, 'update'])->name('address.update');
    Route::patch('address/{id}', [AddressController::class, 'patch'])->name('address.patch');
    Route::delete('address/{id}', [AddressController::class, 'destroy'])->name('address.destroy');

    Route::get('addresstype', [AddressTypeController::class, 'index'])->name('addresstype.index');
    Route::get('addresstype/{id}', [AddressTypeController::class, 'show'])->name('addresstype.show');

    Route::get('region', [RegionController::class, 'index'])->name('region.index');
    Route::get('region/{id}', [RegionController::class, 'show'])->name('region.show');
});
