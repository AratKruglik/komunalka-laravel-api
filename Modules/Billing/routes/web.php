<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Billing\Actions\CreateServiceProvider;
use Modules\Billing\Actions\DeleteServiceProvider;
use Modules\Billing\Actions\Pages\ProviderCreatePage;
use Modules\Billing\Actions\Pages\ProviderEditPage;
use Modules\Billing\Actions\Pages\ProviderIndexPage;
use Modules\Billing\Actions\UpdateServiceProvider;

Route::middleware('auth')->group(function (): void {
    Route::get('providers', ProviderIndexPage::class)->name('providers.index');
    Route::get('providers/create', ProviderCreatePage::class)->name('providers.create');
    Route::post('providers', CreateServiceProvider::class)->name('providers.store');
    Route::get('providers/{provider}/edit', ProviderEditPage::class)->name('providers.edit');
    Route::put('providers/{provider}', UpdateServiceProvider::class)->name('providers.update');
    Route::delete('providers/{provider}', DeleteServiceProvider::class)->name('providers.destroy');
});
