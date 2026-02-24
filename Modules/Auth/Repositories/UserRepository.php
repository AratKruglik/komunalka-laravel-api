<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use App\Repositories\EloquentRepository;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

/** @extends EloquentRepository<User> */
class UserRepository extends EloquentRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->newQuery()->where('email', $email)->first();
    }

    public function findByExternalId(AuthProvider $provider, string $externalId): ?User
    {
        return $this->newQuery()
            ->where('auth_provider', $provider->value)
            ->where('external_id', $externalId)
            ->first();
    }
}
