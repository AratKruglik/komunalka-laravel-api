<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\Actions\CreateMeter;
use Modules\Meter\Actions\DeleteMeter;
use Modules\Meter\Actions\DeleteMeterReading;
use Modules\Meter\Actions\Pages\MeterCreatePage;
use Modules\Meter\Actions\Pages\MeterEditPage;
use Modules\Meter\Actions\Pages\MeterIndexPage;
use Modules\Meter\Actions\Pages\ReadingCreatePage;
use Modules\Meter\Actions\Pages\ReadingIndexPage;
use Modules\Meter\Actions\UpdateMeter;
use Modules\Meter\Actions\UploadMeterPhoto;

Route::middleware('auth')->group(function (): void {
    Route::get('meters', MeterIndexPage::class)->name('meters.index');
    Route::get('meters/create', MeterCreatePage::class)->name('meters.create');
    Route::post('meters', CreateMeter::class)->name('meters.store');
    Route::get('meters/{meter}/edit', MeterEditPage::class)->name('meters.edit');
    Route::put('meters/{meter}', UpdateMeter::class)->name('meters.update');
    Route::delete('meters/{meter}', DeleteMeter::class)->name('meters.destroy');
    Route::post('meters/{meter}/photo', UploadMeterPhoto::class)->name('meters.photo');

    Route::get('readings', ReadingIndexPage::class)->name('readings.index');
    Route::get('readings/create', ReadingCreatePage::class)->name('readings.create');
    Route::post('readings', CreateBatchReadings::class)->name('readings.store');
    Route::delete('readings/{reading}', DeleteMeterReading::class)->name('readings.destroy');
});
