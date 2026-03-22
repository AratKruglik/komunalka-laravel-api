<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Meter\Http\Controllers\Web\MeterController;
use Modules\Meter\Http\Controllers\Web\ReadingController;

Route::middleware('auth')->group(function (): void {
    Route::resource('meters', MeterController::class)->except(['show']);
    Route::post('meters/{meter}/photo', [MeterController::class, 'uploadPhoto'])->name('meters.photo');

    Route::get('readings', [ReadingController::class, 'index'])->name('readings.index');
    Route::get('readings/create', [ReadingController::class, 'create'])->name('readings.create');
    Route::post('readings', [ReadingController::class, 'store'])->name('readings.store');
    Route::delete('readings/{reading}', [ReadingController::class, 'destroy'])->name('readings.destroy');
});
