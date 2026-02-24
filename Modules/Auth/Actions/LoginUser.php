<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTOs\LoginData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;
use Modules\Auth\Services\JwtService;

class LoginUser
{
    use AsAction;

    public function __construct(
        private UserRepositoryInterface $userRepository,
        private JwtService $jwtService,
    ) {}

    /** @return array<string, mixed> */
    public function handle(LoginData $data): array
    {
        $user = $this->userRepository->findByEmail($data->email);

        if (! $user) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if ($user->auth_provider !== AuthProvider::Local) {
            throw ValidationException::withMessages([
                'email' => ["Please use {$user->auth_provider->value} to login."],
            ]);
        }

        if (! Hash::check($data->password, $user->password)) {
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        $user->update(['last_login_at' => now()]);

        return $this->jwtService->generateTokenPair($user);
    }
}
