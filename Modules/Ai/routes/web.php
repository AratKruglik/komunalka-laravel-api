<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Ai\Actions\AskAssistant;
use Modules\Ai\Actions\DecideAssistantApproval;
use Modules\Ai\Actions\GetHelpState;
use Modules\Ai\Actions\Pages\HelpPage;

Route::middleware(['web', 'auth'])->group(function (): void {
    Route::get('help', HelpPage::class)->name('help.index');
    Route::get('help/state', GetHelpState::class)->middleware('throttle:support-ai')->name('help.state');
    Route::post('help/ask', AskAssistant::class)->middleware('throttle:support-ai')->name('help.ask');
    Route::post('help/approve', DecideAssistantApproval::class)->middleware('throttle:support-ai')->name('help.approve');
});
