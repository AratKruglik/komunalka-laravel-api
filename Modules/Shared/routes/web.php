<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Shared\Actions\AskAssistant;
use Modules\Shared\Actions\DecideAssistantApproval;
use Modules\Shared\Actions\Pages\HelpPage;

Route::middleware('auth')->group(function (): void {
    Route::get('help', HelpPage::class)->name('help.index');
    Route::post('help/ask', AskAssistant::class)->middleware('throttle:support-ai')->name('help.ask');
    Route::post('help/approve', DecideAssistantApproval::class)->middleware('throttle:support-ai')->name('help.approve');
});
