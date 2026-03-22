<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Address\Actions\CreateAddress;
use Modules\Address\Actions\DeleteAddress;
use Modules\Address\Actions\Pages\AddressCreatePage;
use Modules\Address\Actions\Pages\AddressEditPage;
use Modules\Address\Actions\Pages\AddressIndexPage;
use Modules\Address\Actions\UpdateAddress;

Route::middleware('auth')->group(function (): void {
    Route::get('addresses', AddressIndexPage::class)->name('addresses.index');
    Route::get('addresses/create', AddressCreatePage::class)->name('addresses.create');
    Route::post('addresses', CreateAddress::class)->name('addresses.store');
    Route::get('addresses/{address}/edit', AddressEditPage::class)->name('addresses.edit');
    Route::put('addresses/{address}', UpdateAddress::class)->name('addresses.update');
    Route::delete('addresses/{address}', DeleteAddress::class)->name('addresses.destroy');
});
