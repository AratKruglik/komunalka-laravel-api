<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class GetAllUsers
{
    use AsAction;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    /** @return Collection<int, User> */
    public function handle(): Collection
    {
        return $this->userRepository->getAllWithAddresses();
    }
}
