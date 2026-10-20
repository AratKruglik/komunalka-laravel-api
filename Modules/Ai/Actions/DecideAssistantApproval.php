<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Http\Requests\DecideAssistantApprovalRequest;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class DecideAssistantApproval
{
    use AsAction;

    public function __construct(
        private readonly ResolveHelpApproval $resolve,
        private readonly ResolveLegacyHelpConversation $resolveLegacy,
        private readonly ApplyPropertySetupApproval $applySetup,
        private readonly UpdatePropertySetupDraft $updateDraft,
        private readonly CreatePropertySetupApproval $createSetupApproval,
        private readonly ApplyMeterReadingApproval $applyMeter,
        private readonly EditMeterReadingApproval $editMeter,
    ) {}

    /**
     * @param  array<string, mixed>  $edits
     * @return array<string, mixed>
     */
    public function handle(User $user, string $conversationId, string $approvalId, string $action, array $edits = [], ?int $version = null): array
    {
        $conversation = HelpConversation::query()->whereKey($conversationId)->first();
        if ($conversation === null) {
            $conversation = $this->resolveLegacy->handle($user, $conversationId);
        }

        abort_unless($conversation->user_id === $user->getKey(), 403);

        $publicApproval = Str::isUuid($approvalId) ? HelpApproval::query()->whereKey($approvalId)->first() : null;
        if ($publicApproval !== null) {
            abort_unless($publicApproval->user_id === $user->getKey() && $publicApproval->conversation_id === $conversation->id, 403);
            abort_if($version === null, 422, 'Вкажіть версію пропозиції.');

            if ($publicApproval->workflow === HelpWorkflow::MeterReading) {
                if ($action === 'edit') {
                    return [
                        ...$this->baseReply('Перевірте змінене показання перед збереженням.', $conversation, HelpWorkflow::MeterReading),
                        'pending_approval' => $this->editMeter->handle($user, $conversation, $publicApproval, $version, $edits),
                    ];
                }
                $result = $this->applyMeter->handle($user, $conversation, $publicApproval, $version, $action);

                return [
                    ...$this->baseReply($result['message'], $conversation, HelpWorkflow::MeterReading),
                    'reading_result' => $result,
                ];
            }

            if ($action === 'edit') {
                abort_if($publicApproval->status !== HelpApprovalStatus::Pending || $publicApproval->expires_at->isPast()
                    || $publicApproval->draft_version !== $version, 409);
                $updated = $this->updateDraft->handle($user, $conversation, $edits, $publicApproval, $version);
                $newApproval = $this->createSetupApproval->handle($user, $updated);
                $reply = [
                    'text' => 'Перевірте оновлені дані перед збереженням.',
                    'sources' => [],
                    'pending_approval' => $this->createSetupApproval->card($newApproval),
                    'reading_result' => null,
                    'setup_result' => null,
                ];
            } else {
                $result = $this->applySetup->handle($user, $conversation, $publicApproval, $version, $action);
                $reply = [
                    'text' => $result['message'],
                    'sources' => [],
                    'pending_approval' => null,
                    'reading_result' => null,
                    'setup_result' => $result,
                ];
            }

            return [...$reply, 'conversation_id' => $conversation->id, 'workflow' => HelpWorkflow::PropertySetup->value];
        }

        $sdkId = $conversation->agent_conversation_ids[HelpWorkflow::MeterReading->value] ?? null;
        if ($sdkId === null) {
            abort(409, ResolveHelpApproval::STALE_MESSAGE);
        }

        $specialized = ($conversation->handoff_summary['legacy_sdk_conversation'] ?? false) !== true;
        $reply = $this->resolve->handle($user, $sdkId, $approvalId, $action, $edits, $specialized);

        return [...$reply, 'conversation_id' => $conversation->id, 'workflow' => HelpWorkflow::MeterReading->value, 'setup_result' => null];
    }

    /** @return array<string, mixed> */
    private function baseReply(string $text, HelpConversation $conversation, HelpWorkflow $workflow): array
    {
        return [
            'text' => $text,
            'sources' => [],
            'conversation_id' => $conversation->id,
            'workflow' => $workflow->value,
            'pending_approval' => null,
            'reading_result' => null,
            'setup_result' => null,
        ];
    }

    public function asController(DecideAssistantApprovalRequest $request): JsonResponse
    {
        return response()->json($this->handle(
            $request->user(),
            $request->validated('conversation_id'),
            $request->validated('approval_id'),
            $request->validated('action'),
            $request->validated('arguments', []),
            $request->validated('version'),
        ));
    }
}
