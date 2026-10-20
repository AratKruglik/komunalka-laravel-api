<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Str;
use Laravel\Ai\Approvals\PendingApproval;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class CreateMeterReadingApproval
{
    public function __construct(private readonly ValidateMeterReadingProposal $validate) {}

    /** @param array<string, mixed> $card
     * @return array<string, mixed>
     */
    public function handle(User $user, HelpConversation $conversation, PendingApproval $pending, array $card): array
    {
        abort_unless($conversation->user_id === $user->getKey() && $pending->tool === PresentHelpReply::RECORD_TOOL
            && ($card['id'] ?? null) === $pending->id, 403);

        $proposal = $this->validate->handle($user, $pending->arguments);
        abort_unless($proposal->photo->help_conversation_id === $conversation->id, 403);
        $payload = array_intersect_key($pending->arguments, array_flip(['photo_id', 'meter_id', 'reading_value', 'reading_date', 'tariff_id']));
        $hash = $this->payloadHash($payload);
        $approval = HelpApproval::query()->firstOrCreate([
            'conversation_id' => $conversation->id,
            'workflow' => HelpWorkflow::MeterReading,
            'draft_version' => $conversation->draft_version,
            'payload_hash' => $hash,
        ], [
            'user_id' => $user->getKey(),
            'status' => HelpApprovalStatus::Pending,
            'payload' => $payload,
            'idempotency_key' => (string) Str::uuid(),
            'expires_at' => $proposal->photo->expires_at->min(now()->addMinutes(15)),
        ]);

        return [...$card, 'id' => $approval->id, 'version' => $approval->draft_version, 'expires_at' => $approval->expires_at->toIso8601String()];
    }

    /** @param array<string, mixed> $payload */
    public function payloadHash(array $payload): string
    {
        ksort($payload);

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
