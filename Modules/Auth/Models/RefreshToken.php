<?php

declare(strict_types=1);

namespace Modules\Auth\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Auth\Database\Factories\RefreshTokenFactory;

class RefreshToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'user_id',
        'expiry_date',
        'is_used',
        'is_revoked',
    ];

    public function casts(): array
    {
        return [
            'expiry_date' => 'datetime',
            'is_used' => 'boolean',
            'is_revoked' => 'boolean',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @param Builder<RefreshToken> $query */
    public function scopeValid(Builder $query): void
    {
        $query->where('is_used', false)
            ->where('is_revoked', false)
            ->where('expiry_date', '>', now());
    }

    protected static function newFactory(): RefreshTokenFactory
    {
        return RefreshTokenFactory::new();
    }
}
