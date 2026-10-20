<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\AgentResponse;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Agents\MeterReadingAgent;
use Modules\Ai\Agents\ProductSupportAgent;
use Modules\Ai\Enums\HelpTurn;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;
use Modules\Ai\Support\AssistantResponder;
use Modules\Auth\Models\User;
use Throwable;

class AnswerHelpQuestion
{
    private const string PHOTO_FALLBACK_TEXT = 'Не вдалося обробити фото. Спробуйте ще раз або внесіть показання вручну.';

    private const string PHOTO_UNAVAILABLE_TEXT = 'Розпізнавання фото зараз недоступне. Внесіть показання вручну у розділі «Внести показання».';

    private const string DEFAULT_PHOTO_QUESTION = 'Внеси показання лічильника з цього фото.';

    private const array MANUAL_ENTRY_SOURCE = ['label' => 'Внести показання', 'path' => '/readings/create'];

    public function __construct(
        private readonly ConversationStore $conversations,
        private readonly AssistantResponder $fallback,
        private readonly StoreAssistantMeterPhoto $storePhoto,
        private readonly ForgetAssistantMeterPhoto $forgetPhoto,
        private readonly DescribeUserMeters $meters,
        private readonly PresentHelpReply $present,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, string $question, ?UploadedFile $photo = null, ?string $conversationId = null, ?HelpConversation $helpConversation = null): array
    {
        $this->assertOwnsConversation($user, $conversationId);

        if ($photo === null && $this->isGreeting($question)) {
            return $this->present->plain(
                'Вітаю! Допоможу з показаннями, лічильниками, адресами, провайдерами та тарифами. Про що хочете дізнатися?',
                [],
                $conversationId,
            );
        }

        if (blank(config('ai.providers.openrouter.key'))) {
            return $photo !== null
                ? $this->present->plain(self::PHOTO_UNAVAILABLE_TEXT, [self::MANUAL_ENTRY_SOURCE], $conversationId)
                : $this->fallbackResponse($question, $conversationId);
        }

        $lock = $helpConversation === null ? Cache::lock('support-ai:'.$user->getKey(), 65) : null;
        if ($lock !== null) {
            abort_unless($lock->get(), 429);
        }

        try {
            return $photo !== null
                ? $this->answerWithPhoto($user, $question, $photo, $conversationId, $helpConversation)
                : $this->answerText($user, $question, $conversationId, $helpConversation);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('Support agent failed', ['exception' => $exception::class]);

            return $photo !== null
                ? $this->photoFallback($conversationId)
                : $this->fallbackResponse($question, $conversationId);
        } finally {
            $lock?->release();
        }
    }

    /** @return array<string, mixed> */
    private function answerText(User $user, string $question, ?string $conversationId, ?HelpConversation $helpConversation): array
    {
        $livePhoto = $this->livePhoto($user, $conversationId, $helpConversation);

        if ($livePhoto === null) {
            if ($helpConversation?->active_workflow === HelpWorkflow::MeterReading) {
                $response = $this->prompt($user, HelpTurn::MeterFollowUp, $question, [], $conversationId, $this->specialized($helpConversation));

                return $this->presentMeterReply($user, $response, $conversationId);
            }

            return $this->answerKnowledgeQuestion($user, $question, $conversationId, $helpConversation);
        }

        $prompt = $question."\n\n".$this->serviceBlock($livePhoto);
        $response = $this->prompt($user, HelpTurn::MeterFollowUp, $prompt, [], $conversationId, $this->specialized($helpConversation));

        return $this->presentMeterReply($user, $response, $conversationId);
    }

    /** @return array<string, mixed> */
    private function answerKnowledgeQuestion(User $user, string $question, ?string $conversationId, ?HelpConversation $helpConversation): array
    {
        $response = $this->prompt($user, HelpTurn::Question, $question, [], $conversationId, $this->specialized($helpConversation));
        $sources = $this->present->sources($response);

        if (blank($response->text) || $sources === []) {
            return $this->fallbackResponse($question, $this->discardUnverifiedTurn($response, $conversationId));
        }

        return $this->present->plain(trim($response->text), $sources, $response->conversationId);
    }

    /** @return array<string, mixed> */
    private function answerWithPhoto(User $user, string $question, UploadedFile $photo, ?string $conversationId, ?HelpConversation $helpConversation): array
    {
        $stored = $this->storePhoto->handle($user, $photo, $conversationId, $helpConversation);

        try {
            $prompt = (trim($question) !== '' ? $question : self::DEFAULT_PHOTO_QUESTION)
                ."\n\n".$this->serviceBlock($stored, withMeters: $this->meters->handle($user));
            $response = $this->prompt(
                $user,
                HelpTurn::MeterPhoto,
                $prompt,
                [Image::fromStorage($stored->path, $stored->disk)],
                $conversationId,
                $this->specialized($helpConversation),
            );
        } catch (Throwable $exception) {
            Log::warning('Support agent failed', ['exception' => $exception::class]);
            $this->forgetPhoto->handle($stored);

            return $this->photoFallback($conversationId);
        }

        if ($response->conversationId !== null) {
            $stored->update(['conversation_id' => $response->conversationId]);
        }

        return $this->presentMeterReply($user, $response, $conversationId, $stored);
    }

    /** @return array<string, mixed> */
    private function presentMeterReply(User $user, AgentResponse $response, ?string $conversationId, ?AssistantMeterPhoto $stored = null): array
    {
        $reply = $this->present->handle($user, $response);

        if ($reply['text'] !== '') {
            return $reply;
        }

        $remainingConversation = $this->discardUnverifiedTurn($response, $conversationId);
        if ($stored !== null) {
            $this->forgetPhoto->handle($stored->refresh());
        }

        return $this->photoFallback($remainingConversation);
    }

    /** @param list<Image> $attachments */
    private function prompt(User $user, HelpTurn $turn, string $prompt, array $attachments, ?string $conversationId, bool $specialized = false): AgentResponse
    {
        $agent = $specialized
            ? ($turn === HelpTurn::Question ? ProductSupportAgent::make() : MeterReadingAgent::make(user: $user))
            : HelpAssistant::make(user: $user, turn: $turn);

        return $agent
            ->continueOrStart($conversationId, as: $user)
            ->prompt(
                $prompt,
                attachments: $attachments,
                provider: 'openrouter',
                model: (string) config($specialized
                    ? ($turn === HelpTurn::Question ? 'ai-support.support_model' : 'ai-support.meter_model')
                    : 'ai-support.model', 'openai/gpt-4o-mini'),
                timeout: 20,
            );
    }

    /** @param list<array<string, mixed>>|null $withMeters */
    private function serviceBlock(AssistantMeterPhoto $photo, ?array $withMeters = null): string
    {
        $timezone = (string) config('app.timezone');
        $lines = [
            '[Службові дані застосунку, не від користувача]',
            'photo_id: '.$photo->getKey(),
            'exif_status: '.$photo->exifStatus(),
            'exif_date: '.($photo->exifDate() ?? 'немає'),
            'сьогодні: '.CarbonImmutable::now($timezone)->toDateString(),
        ];

        if ($withMeters !== null) {
            $lines[] = 'лічильники: '.json_encode($withMeters, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        return implode("\n", $lines);
    }

    private function livePhoto(User $user, ?string $conversationId, ?HelpConversation $helpConversation): ?AssistantMeterPhoto
    {
        if ($conversationId === null && $helpConversation === null) {
            return null;
        }

        $query = AssistantMeterPhoto::query()
            ->active()
            ->where('user_id', $user->getKey());

        $query->where($helpConversation === null ? 'conversation_id' : 'help_conversation_id', $helpConversation === null ? $conversationId : $helpConversation->id);

        return $query->latest()->first();
    }

    private function assertOwnsConversation(User $user, ?string $conversationId): void
    {
        if ($conversationId === null) {
            return;
        }

        abort_unless(
            $this->conversations instanceof VerifiesConversationOwnership
                && $this->conversations->conversationBelongsTo($conversationId, $user->getMorphClass(), $user->getKey()),
            403,
        );
    }

    private function isGreeting(string $question): bool
    {
        return preg_match('/^(привіт|вітаю|добрий день|доброго дня|добрий вечір)[\s!.?]*$/iu', trim($question)) === 1;
    }

    private function specialized(?HelpConversation $conversation): bool
    {
        return $conversation !== null && ($conversation->handoff_summary['legacy_sdk_conversation'] ?? false) !== true;
    }

    /** @return array<string, mixed> */
    private function photoFallback(?string $conversationId): array
    {
        return $this->present->plain(self::PHOTO_FALLBACK_TEXT, [self::MANUAL_ENTRY_SOURCE], $conversationId);
    }

    /** @return array<string, mixed> */
    private function fallbackResponse(string $question, ?string $conversationId): array
    {
        $fallback = $this->fallback->respond($question);

        return $this->present->plain($fallback['text'], $fallback['sources'], $conversationId);
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
