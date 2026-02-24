<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Modules\Auth\Models\RefreshToken;

/** @extends RepositoryInterface<RefreshToken> */
interface RefreshTokenRepositoryInterface extends RepositoryInterface
{
    public function findValidByToken(string $token): ?RefreshToken;

    public function revokeAllForUser(int $userId): int;

    public function deleteExpired(): int;
}
