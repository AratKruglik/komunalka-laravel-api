<?php

declare(strict_types=1);

namespace Modules\Ai\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ai\Actions\ForgetAssistantMeterPhoto;
use Modules\Ai\Database\Factories\AssistantMeterPhotoFactory;
use Modules\Auth\Models\User;

/**
 * @property string $id
 * @property int $user_id
 * @property string|null $conversation_id
 * @property string $disk
 * @property string $path
 * @property CarbonImmutable|null $taken_at
 * @property CarbonImmutable|null $consumed_at
 * @property CarbonImmutable $expires_at
 */
class AssistantMeterPhoto extends Model
{
    use HasFactory;
    use HasUuids;
    use Prunable;

    public const int TTL_HOURS = 24;

    public const string DISK = 'local';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'disk',
        'path',
        'taken_at',
        'consumed_at',
        'expires_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<self> $query */
    public function scopeActive(Builder $query): void
    {
        $query->whereNull('consumed_at')->where('expires_at', '>', now());
    }

    /** @return Builder<self> */
    public function prunable(): Builder
    {
        return self::query()->where('expires_at', '<=', now());
    }

    public function exifStatus(): string
    {
        return match (true) {
            $this->taken_at === null => 'missing',
            $this->taken_at->isAfter(now()) => 'future',
            default => 'exif',
        };
    }

    public function exifDate(): ?string
    {
        return $this->exifStatus() === 'exif' ? $this->taken_at?->toDateString() : null;
    }

    protected static function newFactory(): AssistantMeterPhotoFactory
    {
        return AssistantMeterPhotoFactory::new();
    }

    protected function pruning(): void
    {
        app(ForgetAssistantMeterPhoto::class)->purgeStoredFile($this);
    }

    protected function casts(): array
    {
        return [
            'taken_at' => 'immutable_datetime',
            'consumed_at' => 'immutable_datetime',
            'expires_at' => 'immutable_datetime',
        ];
    }
}
