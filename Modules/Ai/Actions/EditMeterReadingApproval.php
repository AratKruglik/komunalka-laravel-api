<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Laravel\Ai\Approvals\PendingApproval;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class EditMeterReadingApproval
{
    public function __construct(
        private readonly ValidateMeterReadingProposal $validate,
        private readonly PresentHelpReply $present,
        private readonly CreateMeterReadingApproval $create,
    ) {}

    /** @param array<string, mixed> $edits
     * @return array<string, mixed>
     */
    public function handle(User $user, HelpConversation $conversation, HelpApproval $approval, int $version, array $edits): array
    {
        abort_unless($conversation->user_id === $user->getKey() && $approval->user_id === $user->getKey()
            && $approval->conversation_id === $conversation->id && $approval->workflow === HelpWorkflow::MeterReading, 403);

        return DB::transaction(function () use ($user, $conversation, $approval, $version, $edits): array {
            $currentConversation = HelpConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $locked = HelpApproval::query()->whereKey($approval->id)->lockForUpdate()->firstOrFail();
            abort_if($locked->status !== HelpApprovalStatus::Pending || $locked->expires_at->isPast()
                || $locked->draft_version !== $version || $currentConversation->draft_version !== $version, 409,
                ResolveHelpApproval::STALE_MESSAGE);

            $changes = array_intersect_key($edits, array_flip(['reading_value', 'reading_date', 'tariff_id']));
            abort_if($changes === [], 422, 'Вкажіть, що саме потрібно змінити.');
            $payload = [...$locked->payload, ...$changes];
            $proposal = $this->validate->handle($user, $payload);
            abort_unless($proposal->photo->help_conversation_id === $conversation->id, 403);

            $locked->update(['status' => HelpApprovalStatus::Cancelled, 'cancelled_at' => now()]);
            $currentConversation->update(['draft_version' => $version + 1]);
            $pending = new PendingApproval('edited-'.$approval->id, PresentHelpReply::RECORD_TOOL, $payload);
            $card = $this->present->approvalCard($pending->id, $proposal);

            return $this->create->handle($user, $currentConversation, $pending, $card);
        });
    }
}
