<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Repositories\EloquentRepository;
use Modules\Auth\Models\RefreshToken;
use Modules\Auth\Repositories\Contracts\RefreshTokenRepositoryInterface;

/** @extends EloquentRepository<RefreshToken> */
class RefreshTokenRepository extends EloquentRepository implements RefreshTokenRepositoryInterface
{
    public function __construct(RefreshToken $model)
    {
        parent::__construct($model);
    }

    public function findValidByToken(string $token): ?RefreshToken
    {
        return $this->newQuery()->valid()->where('token', $token)->first();
    }

    public function revokeAllForUser(int $userId): int
    {
        return $this->newQuery()
            ->where('user_id', $userId)
            ->where('is_revoked', false)
            ->update(['is_revoked' => true]);
    }

    public function deleteExpired(): int
    {
        return $this->newQuery()
            ->where('expiry_date', '<', now())
            ->delete();
    }
}
