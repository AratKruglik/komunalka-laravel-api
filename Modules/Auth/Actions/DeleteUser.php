<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class DeleteUser
{
    use AsAction;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function handle(User $user): void
    {
        $this->userRepository->delete($user);
    }
}
