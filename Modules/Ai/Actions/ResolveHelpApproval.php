<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\ResolvesPendingApprovals;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Laravel\Ai\Exceptions\ApprovalMismatchException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Agents\HelpAssistant;
use Modules\Ai\Agents\MeterReadingAgent;
use Modules\Ai\Enums\HelpTurn;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Auth\Models\User;
use Modules\Meter\Models\MeterReading;
use Throwable;

class ResolveHelpApproval
{
    use AsAction;

    public const string STALE_MESSAGE = 'Цей запит на підтвердження вже неактуальний.';

    private const string REJECTION_RESULT = 'Користувач скасував запис показання. Нічого не збережено.';

    private const array EDITABLE_ARGUMENTS = ['reading_value', 'reading_date', 'tariff_id'];

    public function __construct(
        private readonly ConversationStore $conversations,
        private readonly PresentHelpReply $present,
        private readonly ForgetAssistantMeterPhoto $forgetPhoto,
    ) {}

    /**
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    public function handle(User $user, string $conversationId, string $approvalId, string $action, array $edits = [], bool $specialized = false): array
    {
        $this->assertOwnsConversation($user, $conversationId);

        $lock = Cache::lock('support-ai:'.$user->getKey(), 65);
        abort_unless($lock->get(), 429);

        try {
            $pending = $this->findPending($conversationId, $approvalId);
            $photo = $this->findPhoto($user, $pending);

            abort_if($action !== 'reject' && ($photo === null || ! $this->isActive($photo)), 409, self::STALE_MESSAGE);

            $reply = $this->resume($user, $conversationId, $pending, $this->decision($action, $pending, $edits), $photo, $specialized);
            $this->forgetWhenSettled($photo, $reply);

            return $reply;
        } finally {
            $lock->release();
        }
    }

    /** @return array<string, mixed> */
    private function resume(User $user, string $conversationId, PendingApproval $pending, Decision $decision, ?AssistantMeterPhoto $photo, bool $specialized): array
    {
        try {
            $agent = $specialized ? MeterReadingAgent::make(user: $user) : HelpAssistant::make(user: $user, turn: HelpTurn::ApprovalResume);
            $response = $agent
                ->continue($conversationId, as: $user)
                ->prompt(
                    Decisions::from([$pending->id => $decision]),
                    provider: 'openrouter',
                    model: (string) config($specialized ? 'ai-support.meter_model' : 'ai-support.model', 'openai/gpt-4o-mini'),
                    timeout: 20,
                );
        } catch (ApprovalMismatchException) {
            abort(409, self::STALE_MESSAGE);
        } catch (Throwable $exception) {
            return $this->recoverSavedReading($exception, $user, $conversationId, $pending, $decision, $photo);
        }

        $reply = $this->present->handle($user, $response);
        $reply['reading_result'] ??= $decision->isRejected()
            ? ['status' => 'rejected', 'message' => 'Запис показання скасовано. Нічого не збережено.', 'reading' => null]
            : ['status' => 'failed', 'message' => 'Не вдалося зберегти показання. Спробуйте ще раз або внесіть їх вручну.', 'reading' => null];
        $reply['pending_approval'] = null;
        $reply['text'] = $reply['text'] !== '' ? $reply['text'] : $reply['reading_result']['message'];

        return $reply;
    }

    /** @return array<string, mixed> */
    private function recoverSavedReading(Throwable $exception, User $user, string $conversationId, PendingApproval $pending, Decision $decision, ?AssistantMeterPhoto $photo): array
    {
        $reading = $decision->isRejected() || $photo === null || $photo->refresh()->consumed_at === null
            ? null
            : $this->savedReading($user, $pending);

        if ($reading === null) {
            throw $exception;
        }

        Log::warning('Help assistant continuation failed after reading was saved', [
            'exception' => $exception::class,
            'user_id' => $user->getKey(),
            'conversation_id' => $conversationId,
        ]);

        $message = 'Показання збережено, але не вдалося сформувати відповідь асистента.';

        return [
            ...$this->present->plain($message, [], $conversationId),
            'reading_result' => ['status' => 'saved', 'message' => $message, 'reading' => $reading],
        ];
    }

    /** @return array<string, mixed>|null */
    private function savedReading(User $user, PendingApproval $pending): ?array
    {
        $reading = MeterReading::query()
            ->where('meter_id', $pending->arguments['meter_id'] ?? null)
            ->whereHas('meter.address.users', fn ($query) => $query->whereKey($user->getKey()))
            ->latest('id')
            ->first();

        return $reading === null ? null : [
            'id' => $reading->getKey(),
            'meter_id' => $reading->meter_id,
            'reading_value' => $reading->reading_value,
            'reading_date' => $reading->reading_date->toDateString(),
            'consumption' => $reading->consumption,
        ];
    }

    /** @param array<string, mixed> $edits */
    private function decision(string $action, PendingApproval $pending, array $edits): Decision
    {
        return match ($action) {
            'approve' => Decision::approve(),
            'edit' => Decision::edit([...$pending->arguments, ...array_intersect_key($edits, array_flip(self::EDITABLE_ARGUMENTS))]),
            default => Decision::reject(self::REJECTION_RESULT),
        };
    }

    private function findPending(string $conversationId, string $approvalId): PendingApproval
    {
        $pending = $this->conversations instanceof ResolvesPendingApprovals
            ? collect($this->conversations->pendingApprovalsFor($conversationId))
                ->first(fn (PendingApproval $approval): bool => $approval->id === $approvalId && $approval->tool === PresentHelpReply::RECORD_TOOL)
            : null;

        abort_if($pending === null, 409, self::STALE_MESSAGE);

        return $pending;
    }

    private function findPhoto(User $user, PendingApproval $pending): ?AssistantMeterPhoto
    {
        $photoId = $pending->arguments['photo_id'] ?? null;

        return is_string($photoId) && Str::isUuid($photoId)
            ? AssistantMeterPhoto::query()->where('user_id', $user->getKey())->find($photoId)
            : null;
    }

    private function isActive(AssistantMeterPhoto $photo): bool
    {
        return $photo->consumed_at === null && $photo->expires_at->isFuture();
    }

    /** @param array<string, mixed> $reply */
    private function forgetWhenSettled(?AssistantMeterPhoto $photo, array $reply): void
    {
        if ($photo !== null && in_array($reply['reading_result']['status'] ?? null, ['saved', 'rejected'], true)) {
            $this->forgetPhoto->handle($photo);
        }
    }

    private function assertOwnsConversation(User $user, string $conversationId): void
    {
        abort_unless(
            $this->conversations instanceof VerifiesConversationOwnership
                && $this->conversations->conversationBelongsTo($conversationId, $user->getMorphClass(), $user->getKey()),
            403,
        );
    }
}
