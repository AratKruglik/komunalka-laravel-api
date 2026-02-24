<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTOs\RegisterUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;
use Modules\Auth\Services\JwtService;

class RegisterUser
{
    use AsAction;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private JwtService $jwtService,
    ) {}

    /** @return array<string, mixed> */
    public function handle(RegisterUserData $data): array
    {
        /** @var \Modules\Auth\Models\User $user */
        $user = $this->userRepository->create([
            'name' => "{$data->firstName} {$data->lastName}",
            'username' => $data->username,
            'first_name' => $data->firstName,
            'last_name' => $data->lastName,
            'phone_number' => $data->phoneNumber,
            'email' => $data->email,
            'password' => $data->password,
            'role' => UserRole::User,
            'auth_provider' => AuthProvider::Local,
            'email_verified' => false,
        ]);

        return $this->jwtService->generateTokenPair($user);
    }
}
