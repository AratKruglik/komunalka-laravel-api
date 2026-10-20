<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

class CreatePropertySetupApproval
{
    public function __construct(private readonly ValidatePropertySetupDraft $validate) {}

    public function handle(User $user, HelpConversation $conversation): HelpApproval
    {
        abort_unless($conversation->user_id === $user->getKey(), 403);

        return DB::transaction(function () use ($user, $conversation): HelpApproval {
            DB::table('users')->where('id', $user->getKey())->lockForUpdate()->first();
            $current = HelpConversation::query()->whereKey($conversation->id)->lockForUpdate()->firstOrFail();
            $payload = $this->validate->handle($user, $current->draft ?? []);
            $hash = $this->payloadHash($payload);
            $existing = HelpApproval::query()->where('conversation_id', $current->id)
                ->where('workflow', HelpWorkflow::PropertySetup)
                ->where('draft_version', $current->draft_version)
                ->where('payload_hash', $hash)->first();

            if ($existing !== null && $existing->status === HelpApprovalStatus::Pending && $existing->expires_at->isFuture()) {
                return $existing;
            }

            if ($existing !== null) {
                $current->increment('draft_version');
                $current->refresh();
            }

            return HelpApproval::query()->create([
                'conversation_id' => $current->id,
                'workflow' => HelpWorkflow::PropertySetup,
                'draft_version' => $current->draft_version,
                'payload_hash' => $hash,
                'user_id' => $user->getKey(),
                'status' => HelpApprovalStatus::Pending,
                'payload' => $payload,
                'idempotency_key' => (string) Str::uuid(),
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }

    /** @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function canonical(array $payload): array
    {
        ksort($payload);
        foreach ($payload as &$value) {
            if (is_array($value)) {
                $value = $this->canonical($value);
            }
        }

        return $payload;
    }

    /** @param array<string, mixed> $payload */
    public function payloadHash(array $payload): string
    {
        return hash('sha256', json_encode($this->canonical($payload), JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /** @return array<string, mixed> */
    public function card(HelpApproval $approval): array
    {
        return [
            'id' => $approval->id,
            'tool' => 'create_property_setup',
            'version' => $approval->draft_version,
            'expires_at' => $approval->expires_at->toIso8601String(),
            'address' => $approval->payload['address'] ?? (isset($approval->payload['address_id']) ? ['id' => $approval->payload['address_id']] : null),
            'provider' => $approval->payload['provider'] ?? null,
            'meter' => $approval->payload['meter'] ?? null,
            'warnings' => [],
        ];
    }
}
