<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;

/** @extends RepositoryInterface<User> */
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByExternalId(AuthProvider $provider, string $externalId): ?User;
}
