<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Auth\Models\User;

class PurgeSupportConversations
{
    public function handle(User $user): void
    {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $ownedConversations = DB::table($conversationsTable)
            ->where('participant_type', $user->getMorphClass())
            ->where('participant_id', $user->getKey());

        DB::table($messagesTable)
            ->whereIn('conversation_id', (clone $ownedConversations)->select('id'))
            ->delete();

        $ownedConversations->delete();
    }
}
