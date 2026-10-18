<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Messages\UserMessage;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Auth\Actions\DeleteUser;
use Modules\Auth\Models\User;

it('removes a deleted users support conversation and messages', function (): void {
    $user = User::factory()->create();
    $store = app(ConversationStore::class);
    $conversationId = $store->storeConversation($user->getMorphClass(), $user->getKey(), 'Питання підтримки');
    $store->storeUserMessage(
        $conversationId,
        $user->getMorphClass(),
        $user->getKey(),
        HelpAssistant::class,
        new UserMessage('Як внести показання?'),
    );

    DeleteUser::run($user);

    expect(DB::table('agent_conversations')->where('id', $conversationId)->exists())->toBeFalse()
        ->and(DB::table('agent_conversation_messages')->where('conversation_id', $conversationId)->exists())->toBeFalse();
});
