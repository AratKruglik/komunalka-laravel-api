<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;

/** @extends RepositoryInterface<User> */
interface UserRepositoryInterface extends RepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function findByExternalId(AuthProvider $provider, string $externalId): ?User;

    /** @return Collection<int, User> */
    public function getAllWithAddresses(): Collection;

    public function findWithAddresses(int|string $id): ?User;
}
