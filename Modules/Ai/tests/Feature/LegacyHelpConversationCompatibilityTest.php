<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\ConversationStore;
use Modules\Ai\Actions\ResolveLegacyHelpConversation;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Symfony\Component\HttpKernel\Exception\HttpException;

it('maps an owned legacy SDK conversation to one public ID', function (): void {
    $user = User::factory()->create();
    $legacyId = app(ConversationStore::class)->storeConversation($user->getMorphClass(), $user->getKey(), 'Довідка');

    $first = app(ResolveLegacyHelpConversation::class)->handle($user, $legacyId);
    $again = app(ResolveLegacyHelpConversation::class)->handle($user, $legacyId);

    expect($first->id)->toBe($again->id)
        ->and($first->agent_conversation_ids[HelpWorkflow::ProductSupport->value])->toBe($legacyId);
});

it('recognizes an active legacy meter photo', function (): void {
    $user = User::factory()->create();
    $legacyId = app(ConversationStore::class)->storeConversation($user->getMorphClass(), $user->getKey(), 'Фото');
    $photo = AssistantMeterPhoto::factory()->for($user)->create(['conversation_id' => $legacyId]);

    $public = app(ResolveLegacyHelpConversation::class)->handle($user, $legacyId);

    expect($public->active_workflow)->toBe(HelpWorkflow::MeterReading)
        ->and($public->agent_conversation_ids[HelpWorkflow::MeterReading->value])->toBe($legacyId)
        ->and($photo->refresh()->help_conversation_id)->toBe($public->id);
});

it('rejects another users legacy SDK ID', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $legacyId = app(ConversationStore::class)->storeConversation($owner->getMorphClass(), $owner->getKey(), 'Приватно');

    app(ResolveLegacyHelpConversation::class)->handle($other, $legacyId);
})->throws(HttpException::class);
