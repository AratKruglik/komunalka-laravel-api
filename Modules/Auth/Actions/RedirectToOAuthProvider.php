<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Laravel\Socialite\Facades\Socialite;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Symfony\Component\HttpFoundation\RedirectResponse;

class RedirectToOAuthProvider
{
    use AsAction;

    public function handle(AuthProvider $provider): RedirectResponse
    {
        return Socialite::driver($provider->value)->redirect();
    }

    public function asController(string $provider): RedirectResponse
    {
        return $this->handle(AuthProvider::from($provider));
    }
}
