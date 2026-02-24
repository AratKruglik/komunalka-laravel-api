<?php

use Illuminate\Support\Facades\Route;
use Modules\Meter\Http\Controllers\BatchMeterReadingsController;
use Modules\Meter\Http\Controllers\LegacyMeterReadingController;
use Modules\Meter\Http\Controllers\MeterController;
use Modules\Meter\Http\Controllers\MeterReadingPhotoController;

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::get('meter', [MeterController::class, 'index'])->name('meter.index');
    Route::get('meter/active', [MeterController::class, 'active'])->name('meter.active');
    Route::get('meter/address/{addressId}', [MeterController::class, 'byAddress'])->name('meter.by-address');
    Route::get('meter/{id}', [MeterController::class, 'show'])->name('meter.show');
    Route::post('meter', [MeterController::class, 'store'])->name('meter.store');
    Route::post('meter/{id}/photo', [MeterController::class, 'uploadPhoto'])->name('meter.upload-photo');
    Route::put('meter/{id}', [MeterController::class, 'update'])->name('meter.update');
    Route::delete('meter/{id}', [MeterController::class, 'destroy'])->name('meter.destroy');

    Route::post('meter-readings/batch', [BatchMeterReadingsController::class, 'store'])->name('meter-readings.store');
    Route::get('meter-readings/address/{addressId}', [BatchMeterReadingsController::class, 'byAddress'])->name('meter-readings.by-address');
    Route::get('meter-readings/{id}', [BatchMeterReadingsController::class, 'show'])->name('meter-readings.show');
    Route::delete('meter-readings/{id}', [BatchMeterReadingsController::class, 'destroy'])->name('meter-readings.destroy');

    Route::post('meterreading', [LegacyMeterReadingController::class, 'store'])->name('legacy-meter-reading.store');
    Route::get('meterreading/{id}', [LegacyMeterReadingController::class, 'show'])->name('legacy-meter-reading.show');
    Route::delete('meterreading/{id}', [LegacyMeterReadingController::class, 'destroy'])->name('legacy-meter-reading.destroy');
});

Route::prefix('v1')->group(function (): void {
    Route::get('meter-readings/photos/{id}/optimized', [MeterReadingPhotoController::class, 'optimized'])->name('meter-readings.photos.optimized');
    Route::get('meter-readings/photos/{id}/thumbnail', [MeterReadingPhotoController::class, 'thumbnail'])->name('meter-readings.photos.thumbnail');
    Route::get('meterreading/images/{id}/optimized', [LegacyMeterReadingController::class, 'imageOptimized'])->name('legacy-meter-reading.images.optimized');
    Route::get('meterreading/images/{id}/thumbnail', [LegacyMeterReadingController::class, 'imageThumbnail'])->name('legacy-meter-reading.images.thumbnail');
});
