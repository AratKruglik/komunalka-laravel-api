<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class GetHelpState
{
    use AsAction;

    public function __construct(
        private readonly PresentHelpReply $present,
        private readonly CreatePropertySetupApproval $setupApproval,
        private readonly ValidateMeterReadingProposal $meterProposal,
    ) {}

    /** @return array<string, mixed> */
    public function handle(User $user, string $conversationId): array
    {
        $conversation = HelpConversation::query()->whereKey($conversationId)->where('user_id', $user->getKey())->first();
        abort_unless($conversation !== null, 403);

        $reply = $this->present->plain('Можемо продовжити розмову.', [], $conversation->id);
        $reply['workflow'] = $conversation->active_workflow?->value;
        $reply['setup_result'] = null;
        $approval = HelpApproval::query()->where('conversation_id', $conversation->id)
            ->where('user_id', $user->getKey())->where('status', HelpApprovalStatus::Pending)
            ->where('expires_at', '>', now())->latest()->first();
        if ($approval === null) {
            return $reply;
        }

        if ($approval->workflow === HelpWorkflow::PropertySetup) {
            $reply['pending_approval'] = $this->setupApproval->card($approval);

            return $reply;
        }

        try {
            $proposal = $this->meterProposal->handle($user, $approval->payload);
            if ($proposal->photo->help_conversation_id === $conversation->id) {
                $reply['pending_approval'] = [
                    ...$this->present->approvalCard($approval->id, $proposal),
                    'version' => $approval->draft_version,
                    'expires_at' => $approval->expires_at->toIso8601String(),
                ];
            }
        } catch (ValidationException) {
            // The photo or meter changed while the browser was closed.
        }

        return $reply;
    }

    public function asController(Request $request): JsonResponse
    {
        $input = $request->validate(['conversation_id' => ['required', 'uuid']]);

        return response()->json($this->handle($request->user(), $input['conversation_id']));
    }
}
