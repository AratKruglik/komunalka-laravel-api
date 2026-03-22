<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Actions\DeleteUser;
use Modules\Auth\Actions\UnlinkOAuthProvider;
use Modules\Auth\Actions\UpdateUser;
use Modules\Auth\DTO\UpdateUserData;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Http\Requests\UpdateUserRequest;
use Modules\Auth\Models\User;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class SettingsController
{
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $connectedProviders = [];
        foreach (AuthProvider::cases() as $provider) {
            if ($provider === AuthProvider::Local) {
                continue;
            }

            $connectedProviders[] = [
                'provider' => $provider->value,
                'is_connected' => $user->auth_provider === $provider,
            ];
        }

        return Inertia::render('Settings/Index', [
            'tab' => $request->query('tab', 'profile'),
            'connectedProviders' => $connectedProviders,
        ]);
    }

    public function updateProfile(UpdateUserRequest $request, UpdateUser $action): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $action->handle(
            $user,
            UpdateUserData::fromRequest($request),
            $request->file('avatar'),
        );

        return back()->with('success', 'Профіль успішно оновлено.');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Поточний пароль є обов\'язковим.',
            'current_password.current_password' => 'Поточний пароль невірний.',
            'new_password.required' => 'Новий пароль є обов\'язковим.',
            'new_password.min' => 'Новий пароль повинен містити щонайменше 8 символів.',
            'new_password.confirmed' => 'Підтвердження нового паролю не збігається.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return back()->with('success', 'Пароль успішно змінено.');
    }

    public function destroyAccount(Request $request, DeleteUser $action): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:web'],
        ], [
            'password.required' => 'Пароль є обов\'язковим для видалення акаунту.',
            'password.current_password' => 'Невірний пароль.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $action->handle($user);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Ваш акаунт було видалено.');
    }

    public function linkOAuth(string $provider): SymfonyRedirectResponse
    {
        $authProvider = AuthProvider::from($provider);

        return Socialite::driver($authProvider->value)->redirect();
    }

    public function unlinkOAuth(string $provider, Request $request, UnlinkOAuthProvider $action): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ], [
            'password.required' => 'Пароль є обов\'язковим для від\'єднання провайдера.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $action->handle($user, $request->string('password')->toString());

        return back()->with('success', 'Провайдер успішно від\'єднано.');
    }
}
