<?php

declare(strict_types=1);

namespace Modules\Ai\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Ai\Models\HelpConversation;
use Modules\Auth\Models\User;

/** @extends Factory<HelpConversation> */
class HelpConversationFactory extends Factory
{
    protected $model = HelpConversation::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'active_workflow' => null,
            'active_agent' => null,
            'draft' => null,
            'draft_version' => 0,
            'next_field' => null,
            'handoff_summary' => null,
            'agent_conversation_ids' => [],
            'expires_at' => null,
        ];
    }
}
