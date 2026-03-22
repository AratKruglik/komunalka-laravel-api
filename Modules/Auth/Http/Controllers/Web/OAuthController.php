<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Services\OAuthService;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class OAuthController
{
    public function __construct(
        private OAuthService $oAuthService,
    ) {}

    public function redirect(string $provider): SymfonyRedirectResponse
    {
        $authProvider = AuthProvider::from($provider);

        return Socialite::driver($authProvider->value)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $authProvider = AuthProvider::from($provider);

        $socialiteUser = Socialite::driver($authProvider->value)->user();
        $user = $this->oAuthService->findOrCreateUser($socialiteUser, $authProvider);

        $user->update(['last_login_at' => now()]);

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
