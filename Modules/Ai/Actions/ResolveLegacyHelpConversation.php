<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Laravel\Ai\Contracts\ConversationStore;
use Laravel\Ai\Contracts\ResolvesPendingApprovals;
use Laravel\Ai\Contracts\VerifiesConversationOwnership;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\AssistantMeterPhoto;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class ResolveLegacyHelpConversation
{
    public function __construct(private readonly ConversationStore $conversations) {}

    public function handle(User $user, string $legacyId): HelpConversation
    {
        abort_unless(
            $this->conversations instanceof VerifiesConversationOwnership
                && $this->conversations->conversationBelongsTo($legacyId, $user->getMorphClass(), $user->getKey()),
            403,
        );

        $existing = HelpConversation::query()
            ->where('user_id', $user->getKey())
            ->where(function ($query) use ($legacyId): void {
                foreach (HelpWorkflow::cases() as $workflow) {
                    $query->orWhere('agent_conversation_ids->'.$workflow->value, $legacyId);
                }
            })->first();

        if ($existing !== null) {
            $this->linkPhotos($user, $legacyId, $existing);

            return $existing;
        }

        $hasPhoto = AssistantMeterPhoto::query()
            ->where('user_id', $user->getKey())
            ->where('conversation_id', $legacyId)
            ->active()
            ->exists();
        $hasMeterApproval = $this->conversations instanceof ResolvesPendingApprovals
            && collect($this->conversations->pendingApprovalsFor($legacyId))
                ->contains(fn ($approval): bool => $approval->tool === PresentHelpReply::RECORD_TOOL);
        $workflow = $hasPhoto || $hasMeterApproval ? HelpWorkflow::MeterReading : HelpWorkflow::ProductSupport;

        $conversation = HelpConversation::query()->create([
            'user_id' => $user->getKey(),
            'active_workflow' => $workflow,
            'active_agent' => $workflow,
            'agent_conversation_ids' => [$workflow->value => $legacyId],
            'handoff_summary' => ['legacy_sdk_conversation' => true],
        ]);

        $this->linkPhotos($user, $legacyId, $conversation);

        return $conversation;
    }

    private function linkPhotos(User $user, string $legacyId, HelpConversation $conversation): void
    {
        AssistantMeterPhoto::query()->active()
            ->where('user_id', $user->getKey())
            ->where('conversation_id', $legacyId)
            ->whereNull('help_conversation_id')
            ->update(['help_conversation_id' => $conversation->id]);
    }
}
