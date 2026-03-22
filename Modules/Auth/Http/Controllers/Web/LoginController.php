<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Actions\LoginUser;
use Modules\Auth\DTO\LoginData;
use Modules\Auth\Http\Requests\LoginRequest;

class LoginController
{
    public function show(): Response
    {
        return Inertia::render('Auth/Login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $user = LoginUser::run(LoginData::fromRequest($request));

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }
}
