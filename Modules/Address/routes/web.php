<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Address\Http\Controllers\Web\AddressController;

Route::middleware('auth')->group(function (): void {
    Route::resource('addresses', AddressController::class)->except(['show']);
});
