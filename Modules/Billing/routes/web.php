<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Billing\Http\Controllers\Web\ServiceProviderController;

Route::middleware('auth')->group(function (): void {
    Route::resource('providers', ServiceProviderController::class)->except(['show']);
});
