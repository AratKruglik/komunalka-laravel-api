<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class GetOrCreateHelpConversation
{
    public function handle(User $user, ?string $conversationId): HelpConversation
    {
        if ($conversationId === null) {
            return HelpConversation::query()->create([
                'user_id' => $user->getKey(),
                'agent_conversation_ids' => [],
            ]);
        }

        $conversation = HelpConversation::query()
            ->whereKey($conversationId)
            ->where('user_id', $user->getKey())
            ->first();

        abort_unless($conversation !== null, 403);

        return $conversation;
    }
}
