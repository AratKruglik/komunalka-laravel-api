<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Ai\Migrations\AiMigration;

return new class extends AiMigration
{
    /**
     * Upgrade the Laravel AI SDK 0.x conversation layout without losing its history.
     */
    public function up(): void
    {
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $legacyConversationIndex = "{$conversationsTable}_user_id_updated_at_index";
        $legacyMessageUserIndex = "{$messagesTable}_user_id_index";

        if (! Schema::hasTable($conversationsTable) || ! Schema::hasColumn($conversationsTable, 'user_id')) {
            return;
        }

        Schema::table($conversationsTable, function (Blueprint $table): void {
            $table->string('participant_type')->nullable()->after('id');
            $table->unsignedBigInteger('participant_id')->nullable()->after('participant_type');
        });

        Schema::table($messagesTable, function (Blueprint $table): void {
            $table->string('participant_type')->nullable()->after('conversation_id');
            $table->unsignedBigInteger('participant_id')->nullable()->after('participant_type');
            $table->longText('steps')->nullable()->after('attachments');
            $table->string('status', 25)->nullable()->after('meta');
        });

        $participantType = $this->userParticipantType();

        DB::table($conversationsTable)
            ->whereNotNull('user_id')
            ->update([
                'participant_type' => $participantType,
                'participant_id' => DB::raw('user_id'),
            ]);

        DB::table($messagesTable)
            ->orderBy('id')
            ->each(function (object $message) use ($messagesTable, $participantType): void {
                DB::table($messagesTable)
                    ->where('id', $message->id)
                    ->update([
                        'participant_type' => $message->user_id === null ? null : $participantType,
                        'participant_id' => $message->user_id,
                        'steps' => $this->stepsFor($message),
                        'status' => 'completed',
                    ]);
            });

        Schema::table($conversationsTable, function (Blueprint $table) use ($legacyConversationIndex): void {
            $table->dropIndex($legacyConversationIndex);
            $table->dropColumn('user_id');
            $table->index(['participant_type', 'participant_id', 'updated_at'], 'participant_updated_at_index');
        });

        Schema::table($messagesTable, function (Blueprint $table) use ($legacyMessageUserIndex): void {
            $table->dropIndex('conversation_index');
            $table->dropIndex($legacyMessageUserIndex);
            $table->dropColumn(['user_id', 'tool_calls', 'tool_results']);
            $table->longText('steps')->nullable(false)->change();
            $table->string('status', 25)->nullable(false)->change();
            $table->index(['conversation_id', 'participant_type', 'participant_id', 'updated_at'], 'conversation_index');
            $table->index(['participant_type', 'participant_id', 'agent'], 'participant_index');
        });
    }

    /**
     * The old schema stores a single user model, while AI SDK 1.x stores a polymorphic participant.
     */
    private function userParticipantType(): string
    {
        $model = config('auth.providers.users.model');

        return is_string($model) && class_exists($model)
            ? (new $model)->getMorphClass()
            : 'Modules\\Auth\\Models\\User';
    }

    /**
     * Fold 0.x tool calls and results into the per-step shape expected by AI SDK 1.x.
     */
    private function stepsFor(object $message): string
    {
        $toolCalls = $this->decodeJson($message->tool_calls);
        $results = collect($this->decodeJson($message->tool_results))
            ->filter(fn (mixed $result): bool => is_array($result))
            ->keyBy(fn (array $result): string => (string) ($result['id'] ?? $result['tool_call_id'] ?? ''));

        $toolCalls = array_values(array_map(function (mixed $toolCall) use ($results): array {
            if (! is_array($toolCall)) {
                return [];
            }

            $id = (string) ($toolCall['id'] ?? '');
            $result = $id === '' ? null : $results->get($id);

            return [
                ...$toolCall,
                ...is_array($result) ? $result : [],
            ];
        }, $toolCalls));

        return json_encode([[
            'content' => (string) $message->content,
            'tool_calls' => $toolCalls,
            'reasoning' => '',
            'replay_blocks' => [],
            'provider_tool_calls' => [],
        ]], JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<int, mixed>
     */
    private function decodeJson(?string $json): array
    {
        $decoded = json_decode($json ?? '', true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * This migration cannot safely reconstruct 0.x columns from 1.x steps. Keeping the upgraded
     * schema prevents a rollback from silently destroying conversation history.
     */
    public function down(): void
    {
        // Intentionally irreversible.
    }
};
