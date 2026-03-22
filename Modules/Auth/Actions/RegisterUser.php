<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\RegisterUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Enums\UserRole;
use Modules\Auth\Http\Requests\RegisterRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class RegisterUser
{
    use AsAction;

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(RegisterUserData $data): User
    {
        /** @var User $user */
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

        return $user;
    }

    public function asController(RegisterRequest $request): RedirectResponse
    {
        $user = $this->handle(RegisterUserData::fromRequest($request));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
