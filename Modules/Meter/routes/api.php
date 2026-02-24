<?php

use Illuminate\Support\Facades\Route;
use Modules\Meter\Http\Controllers\BatchMeterReadingsController;
use Modules\Meter\Http\Controllers\LegacyMeterReadingController;
use Modules\Meter\Http\Controllers\MeterController;
use Modules\Meter\Http\Controllers\MeterReadingPhotoController;

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::get('meter', [MeterController::class, 'index']);
    Route::get('meter/active', [MeterController::class, 'active']);
    Route::get('meter/address/{addressId}', [MeterController::class, 'byAddress']);
    Route::get('meter/{id}', [MeterController::class, 'show']);
    Route::post('meter', [MeterController::class, 'store']);
    Route::post('meter/{id}/photo', [MeterController::class, 'uploadPhoto']);
    Route::put('meter/{id}', [MeterController::class, 'update']);
    Route::delete('meter/{id}', [MeterController::class, 'destroy']);

    Route::post('meter-readings/batch', [BatchMeterReadingsController::class, 'store']);
    Route::get('meter-readings/address/{addressId}', [BatchMeterReadingsController::class, 'byAddress']);
    Route::get('meter-readings/{id}', [BatchMeterReadingsController::class, 'show']);
    Route::delete('meter-readings/{id}', [BatchMeterReadingsController::class, 'destroy']);

    Route::post('meterreading', [LegacyMeterReadingController::class, 'store']);
    Route::get('meterreading/{id}', [LegacyMeterReadingController::class, 'show']);
    Route::delete('meterreading/{id}', [LegacyMeterReadingController::class, 'destroy']);
});

Route::prefix('v1')->group(function (): void {
    Route::get('meter-readings/photos/{id}/optimized', [MeterReadingPhotoController::class, 'optimized']);
    Route::get('meter-readings/photos/{id}/thumbnail', [MeterReadingPhotoController::class, 'thumbnail']);
    Route::get('meterreading/images/{id}/optimized', [LegacyMeterReadingController::class, 'imageOptimized']);
    Route::get('meterreading/images/{id}/thumbnail', [LegacyMeterReadingController::class, 'imageThumbnail']);
});
