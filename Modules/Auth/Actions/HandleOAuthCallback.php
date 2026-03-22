<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;
use Modules\Auth\Services\OAuthService;

class HandleOAuthCallback
{
    use AsAction;

    public function __construct(
        private OAuthService $oAuthService,
    ) {}

    public function handle(AuthProvider $provider): User
    {
        $socialiteUser = Socialite::driver($provider->value)->user();
        $user = $this->oAuthService->findOrCreateUser($socialiteUser, $provider);
        $user->update(['last_login_at' => now()]);

        return $user;
    }

    public function asController(string $provider, Request $request): RedirectResponse
    {
        $authProvider = AuthProvider::from($provider);

        try {
            $user = $this->handle($authProvider);
        } catch (\Throwable) {
            return redirect()->route('login')->with('error', 'Помилка авторизації через провайдер. Спробуйте ще раз.');
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
