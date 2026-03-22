<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Auth\Actions\RegisterUser;
use Modules\Auth\DTO\RegisterUserData;
use Modules\Auth\Http\Requests\RegisterRequest;

class RegisterController
{
    public function show(): Response
    {
        return Inertia::render('Auth/Register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = RegisterUser::run(RegisterUserData::fromRequest($request));

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
