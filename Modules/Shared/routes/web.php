<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Actions\AskAssistant;
use Modules\Shared\Actions\Pages\HelpPage;

Route::middleware('auth')->group(function (): void {
    Route::get('help', HelpPage::class)->name('help.index');
    Route::post('help/ask', AskAssistant::class)->middleware('throttle:60,1')->name('help.ask');
});
