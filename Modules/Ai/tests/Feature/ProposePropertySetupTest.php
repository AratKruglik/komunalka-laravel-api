<?php

declare(strict_types=1);

use Laravel\Ai\Tools\Request;
use Modules\Address\Models\Address;
use Modules\Ai\Models\HelpConversation;
use Modules\Ai\Tools\ProposePropertySetup;

it('lets the proposal tool update only draft fields and creates no address', function (): void {
    $conversation = HelpConversation::factory()->create();
    $tool = app(ProposePropertySetup::class, ['user' => $conversation->user, 'conversation' => $conversation]);

    $result = json_decode((string) $tool->handle(new Request(['payload' => json_encode([
        'address' => ['city' => 'Київ', 'user_id' => 42], 'user_id' => 42,
    ], JSON_THROW_ON_ERROR)])), true, 512, JSON_THROW_ON_ERROR);

    expect($result['status'])->toBe('draft_updated')
        ->and($conversation->refresh()->draft)->toBe(['address' => ['city' => 'Київ']])
        ->and(Address::query()->count())->toBe(0);
});
