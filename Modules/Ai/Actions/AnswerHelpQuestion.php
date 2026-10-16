<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\Data\ToolResult;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Auth\Models\User;
use Modules\Shared\Support\AssistantResponder;
use Throwable;

class AnswerHelpQuestion
{
    public function __construct(
        private readonly ConversationStore $conversations,
        private readonly AssistantResponder $fallback,
    ) {}

    /** @return array{text: string, sources: list<array{label: string, path: string}>, conversation_id: ?string} */
    public function handle(User $user, string $question, ?string $conversationId = null): array
    {
        if ($conversationId !== null) {
            abort_unless(
                $this->conversations instanceof VerifiesConversationOwnership
                    && $this->conversations->conversationBelongsTo($conversationId, $user->getMorphClass(), $user->getKey()),
                403,
            );
        }

        if (preg_match('/^(привіт|вітаю|добрий день|доброго дня|добрий вечір)[\s!.?]*$/iu', trim($question)) === 1) {
            return [
                'text' => 'Вітаю! Допоможу з показаннями, лічильниками, адресами, провайдерами та тарифами. Про що хочете дізнатися?',
                'sources' => [],
                'conversation_id' => $conversationId,
            ];
        }

        if (blank(config('ai.providers.openrouter.key'))) {
            return $this->fallbackResponse($question, $conversationId);
        }

        $lock = Cache::lock('support-ai:'.$user->getKey(), 65);
        abort_unless($lock->get(), 429);

        try {
            $agent = HelpAssistant::make()->continueOrStart($conversationId, as: $user);
            $response = $agent->prompt(
                $question,
                provider: 'openrouter',
                model: (string) config('ai-support.model', 'openai/gpt-4o-mini'),
                timeout: 20,
            );

            $sources = $this->sourcesFromToolResults($response->toolResults->all());
            if (blank($response->text) || $sources === []) {
                return $this->fallbackResponse($question, $this->discardUnverifiedTurn($response, $conversationId));
            }

            return [
                'text' => trim($response->text),
                'sources' => $sources,
                'conversation_id' => $response->conversationId,
            ];
        } catch (Throwable $exception) {
            Log::warning('Support agent failed', ['exception' => $exception::class]);

            return $this->fallbackResponse($question, $conversationId);
        } finally {
            $lock->release();
        }
    }

    /** @param list<ToolResult> $results
     * @return list<array{label: string, path: string}>
     */
    private function sourcesFromToolResults(array $results): array
    {
        $allowedSources = config('ai-support.sources', []);
        $sources = [];

        foreach ($results as $result) {
            if ($result->name !== 'search_support_knowledge' || ! $result->successful()) {
                continue;
            }

            $payload = json_decode($result->text(), true);
            foreach (($payload['results'] ?? []) as $passage) {
                $sourcePath = $passage['source_path'] ?? null;
                if (is_string($sourcePath) && isset($allowedSources[$sourcePath])) {
                    $source = $allowedSources[$sourcePath];
                    $sources[$source['path']] = $source;
                }
            }
        }

        return array_values($sources);
    }

    /** @return array{text: string, sources: list<array{label: string, path: string}>, conversation_id: ?string} */
    private function fallbackResponse(string $question, ?string $conversationId): array
    {
        return [...$this->fallback->respond($question), 'conversation_id' => $conversationId];
    }

    private function discardUnverifiedTurn(AgentResponse $response, ?string $previousConversationId): ?string
    {
        if ($response->conversationId === null) {
            return $previousConversationId;
        }

        $messagesTable = config('ai.conversations.tables.messages', 'agent_conversation_messages');
        $conversationsTable = config('ai.conversations.tables.conversations', 'agent_conversations');
        $connection = DB::connection(config('ai.conversations.connection'));
        $storedMessageIds = array_filter([$response->userMessageId, $response->assistantMessageId]);

        if ($storedMessageIds !== []) {
            $connection->table($messagesTable)
                ->where('conversation_id', $response->conversationId)
                ->whereIn('id', $storedMessageIds)
                ->delete();
        }

        if ($previousConversationId === null && ! $connection->table($messagesTable)->where('conversation_id', $response->conversationId)->exists()) {
            $connection->table($conversationsTable)->where('id', $response->conversationId)->delete();

            return null;
        }

        return $response->conversationId;
    }
}
