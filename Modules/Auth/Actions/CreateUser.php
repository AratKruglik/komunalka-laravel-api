<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\CreateUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class CreateUser
{
    use AsAction;

    public function __construct(private UserRepositoryInterface $userRepository) {}

    public function handle(CreateUserData $data): User
    {
        /** @var User */
        return $this->userRepository->create([
            'name' => "{$data->firstName} {$data->lastName}",
            'username' => $data->username,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'phone_number' => $data->phoneNumber,
            'email' => $data->email,
            'password' => Hash::make($data->password),
            'role' => $data->role,
            'auth_provider' => AuthProvider::Local,
            'email_verified' => false,
        ]);
    }
}
