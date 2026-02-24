<?php

use Illuminate\Support\Facades\Route;
use Modules\Export\Http\Controllers\ExportController;

Route::prefix('v1')->middleware('auth:api')->group(function (): void {
    Route::post('export/meter-readings', [ExportController::class, 'meterReadings']);
});
