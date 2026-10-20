<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ai\Database\Factories\HelpConversationFactory;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Auth\Models\User;

/**
 * @property string $id
 * @property int $user_id
 * @property HelpWorkflow|null $active_workflow
 * @property HelpWorkflow|null $active_agent
 * @property array<string, mixed>|null $draft
 * @property int $draft_version
 * @property string|null $next_field
 * @property array<string, mixed>|null $handoff_summary
 * @property array<string, string>|null $agent_conversation_ids
 * @property CarbonImmutable|null $expires_at
 */
class HelpConversation extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'user_id',
        'active_workflow',
        'active_agent',
        'draft',
        'draft_version',
        'next_field',
        'handoff_summary',
        'agent_conversation_ids',
        'expires_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): HelpConversationFactory
    {
        return HelpConversationFactory::new();
    }

    protected function casts(): array
    {
        return [
            'active_workflow' => HelpWorkflow::class,
            'active_agent' => HelpWorkflow::class,
            'draft' => 'array',
            'draft_version' => 'integer',
            'handoff_summary' => 'array',
            'agent_conversation_ids' => 'array',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
