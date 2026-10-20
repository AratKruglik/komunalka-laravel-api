<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\ResolvesPendingApprovals;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Agents\PropertySetupAgent;
use Modules\Ai\Enums\HelpIntent;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Http\Requests\AskAssistantRequest;
use Modules\Ai\Models\HelpConversation;
use Modules\Ai\Support\AssistantResponder;
use Modules\Auth\Models\User;
use Throwable;

class AskAssistant
{
    use AsAction;

    public function __construct(
        private readonly AnswerHelpQuestion $answer,
        private readonly GetOrCreateHelpConversation $getConversation,
        private readonly ResolveLegacyHelpConversation $resolveLegacy,
        private readonly RouteHelpTurn $router,
        private readonly PresentHelpReply $present,
        private readonly AssistantResponder $fallback,
        private readonly CreatePropertySetupApproval $setupApproval,
        private readonly CreateMeterReadingApproval $meterApproval,
        private readonly ConversationStore $sdkConversations,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, string $question, ?UploadedFile $photo = null, ?string $conversationId = null): array
    {
        $lock = Cache::lock('support-ai:'.$user->getKey(), 65);
        abort_unless($lock->get(), 429);

        try {
            return $this->respond($user, $question, $photo, $conversationId);
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, mixed> */
    private function respond(User $user, string $question, ?UploadedFile $photo, ?string $conversationId): array
    {
        $conversation = $this->conversation($user, $conversationId);
        $intent = $this->router->handle($conversation, $question, $photo);

        if ($intent === HelpIntent::Clarify) {
            $fallback = $this->fallback->respond($question);
            $reply = $this->present->plain($fallback['text'], $fallback['sources'], $conversation->id);

            return [...$reply, 'workflow' => 'clarify', 'setup_result' => null];
        }

        $workflow = HelpWorkflow::from($intent->value);
        $conversation->update(['active_workflow' => $workflow, 'active_agent' => $workflow]);

        if ($workflow === HelpWorkflow::PropertySetup) {
            return $this->answerSetup($user, $question, $conversation);
        }

        $mapping = $conversation->agent_conversation_ids ?? [];
        $reply = $this->answer->handle($user, $question, $photo, $mapping[$workflow->value] ?? null, $conversation);
        if (is_string($reply['conversation_id'] ?? null)) {
            $mapping[$workflow->value] = $reply['conversation_id'];
            $conversation->update(['agent_conversation_ids' => $mapping]);
        }

        if ($workflow === HelpWorkflow::MeterReading && is_array($reply['pending_approval'] ?? null)) {
            $sdkId = $mapping[$workflow->value] ?? null;
            $pending = $sdkId !== null && $this->sdkConversations instanceof ResolvesPendingApprovals
                ? collect($this->sdkConversations->pendingApprovalsFor($sdkId))->first(
                    fn (PendingApproval $approval): bool => $approval->id === $reply['pending_approval']['id'],
                )
                : null;
            if ($pending === null) {
                $reply['pending_approval'] = null;
                $reply['text'] = 'Не вдалося підготувати підтвердження показання. Надішліть фото ще раз.';
            } else {
                $reply['pending_approval'] = $this->meterApproval->handle($user, $conversation, $pending, $reply['pending_approval']);
            }
        }

        return [...$reply, 'conversation_id' => $conversation->id, 'workflow' => $workflow->value, 'setup_result' => null];
    }

    /** @return array<string, mixed> */
    private function answerSetup(User $user, string $question, HelpConversation $conversation): array
    {
        $mapping = $conversation->agent_conversation_ids ?? [];
        $text = 'Допоможу налаштувати адресу, провайдера або лічильник. Який об’єкт хочете додати?';
        try {
            $response = PropertySetupAgent::make(user: $user, conversation: $conversation)
                ->continueOrStart($mapping[HelpWorkflow::PropertySetup->value] ?? null, as: $user)
                ->prompt($question, provider: 'openrouter', model: (string) config('ai-support.setup_model'), timeout: 20);
            $text = trim($response->text) ?: $text;
            if ($response->conversationId !== null) {
                $mapping[HelpWorkflow::PropertySetup->value] = $response->conversationId;
                $conversation->update(['agent_conversation_ids' => $mapping]);
            }
        } catch (Throwable) {
            $text = 'Не вдалося підготувати налаштування. Спробуйте ще раз або відкрийте відповідний розділ застосунку.';
        }

        $reply = $this->present->plain($text, [], $conversation->id);
        try {
            $approval = $this->setupApproval->handle($user, $conversation->refresh());
            $reply['pending_approval'] = $this->setupApproval->card($approval);
            $reply['text'] = 'Перевірте адресу, провайдера та лічильник перед збереженням.';
        } catch (ValidationException) {
            // An incomplete draft remains available for the next turn.
        }

        return [...$reply, 'workflow' => HelpWorkflow::PropertySetup->value, 'setup_result' => null];
    }

    private function conversation(User $user, ?string $conversationId): HelpConversation
    {
        if ($conversationId === null || HelpConversation::query()->whereKey($conversationId)->exists()) {
            return $this->getConversation->handle($user, $conversationId);
        }

        return $this->resolveLegacy->handle($user, $conversationId);
    }

    public function asController(AskAssistantRequest $request): JsonResponse
    {
        return response()->json(
            $this->handle($request->user(), $request->string('question')->toString(), $request->file('photo'), $request->validated('conversation_id')),
        );
    }
}
