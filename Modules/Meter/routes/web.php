<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Meter\Http\Controllers\Web\MeterController;

Route::middleware('auth')->group(function (): void {
    Route::resource('meters', MeterController::class)->except(['show']);
    Route::post('meters/{meter}/photo', [MeterController::class, 'uploadPhoto'])->name('meters.photo');
});
