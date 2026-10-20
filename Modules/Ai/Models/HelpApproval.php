<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ai\Database\Factories\HelpApprovalFactory;
use Modules\Ai\Enums\HelpApprovalStatus;
use Modules\Ai\Enums\HelpWorkflow;
use Modules\Auth\Models\User;

/**
 * @property string $id
 * @property string $conversation_id
 * @property int $user_id
 * @property HelpWorkflow $workflow
 * @property HelpApprovalStatus $status
 * @property int $draft_version
 * @property array<string, mixed> $payload
 * @property string $payload_hash
 * @property string $idempotency_key
 * @property array<string, mixed>|null $result
 * @property CarbonImmutable $expires_at
 */
class HelpApproval extends Model
{
    use HasFactory;
    use HasUuids;

    protected $fillable = [
        'conversation_id',
        'user_id',
        'workflow',
        'status',
        'draft_version',
        'payload',
        'payload_hash',
        'idempotency_key',
        'result',
        'expires_at',
        'executed_at',
        'cancelled_at',
    ];

    /** @return BelongsTo<HelpConversation, $this> */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(HelpConversation::class, 'conversation_id');
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory(): HelpApprovalFactory
    {
        return HelpApprovalFactory::new();
    }

    protected function casts(): array
    {
        return [
            'workflow' => HelpWorkflow::class,
            'status' => HelpApprovalStatus::class,
            'draft_version' => 'integer',
            'payload' => 'array',
            'result' => 'array',
            'expires_at' => 'immutable_datetime',
            'executed_at' => 'immutable_datetime',
            'cancelled_at' => 'immutable_datetime',
        ];
    }
}
