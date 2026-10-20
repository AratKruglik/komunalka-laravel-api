<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Ai\Tools\Request;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Ai\Tools\RecordMeterReading;
use Modules\Auth\Models\User;

class ApplyMeterReadingApproval
{
    public function __construct(
        private readonly ValidateMeterReadingProposal $validate,
        private readonly ForgetAssistantMeterPhoto $forgetPhoto,
        private readonly CreateMeterReadingApproval $proposals,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, HelpConversation $conversation, HelpApproval $approval, int $version, string $action): array
    {
        abort_unless($conversation->user_id === $user->getKey() && $approval->user_id === $user->getKey()
            && $approval->conversation_id === $conversation->id && $approval->workflow === HelpWorkflow::MeterReading, 403);

        $photo = null;
        $result = DB::transaction(function () use ($user, $conversation, $approval, $version, $action, &$photo): array {
            $locked = HelpApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            if ($locked->status === HelpApprovalStatus::Applied && $action === 'approve') {
                return $locked->result;
            }

            abort_if($locked->status !== HelpApprovalStatus::Pending || $locked->expires_at->isPast()
                || $locked->draft_version !== $version, 409, ResolveHelpApproval::STALE_MESSAGE);
            $photo = AssistantMeterPhoto::query()->whereKey($locked->payload['photo_id'] ?? null)
                ->where('user_id', $user->getKey())->where('help_conversation_id', $conversation->id)
                ->lockForUpdate()->first();
            abort_if($photo === null || $photo->consumed_at !== null || $photo->expires_at->isPast(), 409, ResolveHelpApproval::STALE_MESSAGE);

            if ($action === 'reject') {
                $locked->update(['status' => HelpApprovalStatus::Cancelled, 'cancelled_at' => now()]);

                return ['status' => 'rejected', 'message' => 'Запис показання скасовано. Нічого не збережено.', 'reading' => null];
            }

            abort_unless($action === 'approve', 422);
            abort_if($this->proposals->payloadHash($locked->payload) !== $locked->payload_hash, 409,
                ResolveHelpApproval::STALE_MESSAGE);
            $this->validate->handle($user, $locked->payload);
            $tool = app(RecordMeterReading::class, ['user' => $user]);
            $result = json_decode((string) $tool->handle(new Request($locked->payload)), true, 512, JSON_THROW_ON_ERROR);
            if (($result['status'] ?? null) !== 'saved') {
                throw ValidationException::withMessages(['approval_id' => $result['message'] ?? 'Не вдалося зберегти показання.']);
            }

            $locked->update(['status' => HelpApprovalStatus::Applied, 'result' => $result, 'executed_at' => now()]);

            return $result;
        });

        if ($photo !== null && in_array($result['status'] ?? null, ['saved', 'rejected'], true)) {
            $this->forgetPhoto->handle($photo);
        }

        return $result;
    }
}
