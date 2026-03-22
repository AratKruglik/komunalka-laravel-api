<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Enums\AuthProvider;
use Modules\Auth\Models\User;

class SettingsPage
{
    use AsAction;

    public function handle(Request $request): Response
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
}
