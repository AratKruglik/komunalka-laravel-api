<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetUser
{
    use AsAction;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function handle(int $id): User
    {
        $user = $this->userRepository->findWithAddresses($id);

        abort_if($user === null, Response::HTTP_NOT_FOUND);

        return $user;
    }
}
