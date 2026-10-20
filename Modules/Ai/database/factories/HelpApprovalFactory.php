<?php

declare(strict_types=1);

namespace Modules\Ai\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Ai\Models\HelpApproval;
use Modules\Ai\Models\HelpConversation;

/** @extends Factory<HelpApproval> */
class HelpApprovalFactory extends Factory
{
    protected $model = HelpApproval::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'conversation_id' => HelpConversation::factory(),
            'user_id' => fn (array $attributes): int => HelpConversation::query()->findOrFail($attributes['conversation_id'])->user_id,
            'workflow' => HelpWorkflow::PropertySetup,
            'status' => HelpApprovalStatus::Pending,
            'draft_version' => 0,
            'payload' => [],
            'payload_hash' => hash('sha256', '{}'),
            'idempotency_key' => (string) Str::uuid(),
            'result' => null,
            'expires_at' => now()->addMinutes(15),
        ];
    }
}
