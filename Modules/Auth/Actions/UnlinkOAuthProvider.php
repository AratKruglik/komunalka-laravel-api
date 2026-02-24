<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;

class UnlinkOAuthProvider
{
    use AsAction;

    public function handle(User $user, string $password): void
    {
        if (! $user->password) {
            throw ValidationException::withMessages([
                'password' => ['You must set a password before unlinking OAuth provider.'],
            ]);
        }

        if (! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => ['Invalid password.']]);
        }

        $user->update([
            'auth_provider' => AuthProvider::Local,
            'external_id' => null,
        ]);
    }
}
