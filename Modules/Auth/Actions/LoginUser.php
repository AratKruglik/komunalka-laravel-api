<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Http\Requests\LoginRequest;
use Modules\Auth\Models\User;
use Modules\Auth\Repositories\Contracts\UserRepositoryInterface;

class LoginUser
{
    use AsAction;

    public function __construct(
        private UserRepositoryInterface $userRepository,
    ) {}

    public function handle(LoginData $data): User
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

        return $user;
    }

    public function asController(LoginRequest $request): RedirectResponse
    {
        $user = $this->handle(LoginData::fromRequest($request));

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
