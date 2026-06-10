<?php

declare(strict_types=1);

namespace Modules\Auth\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;

class ResetPasswordPage
{
    use AsAction;

    public function handle(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $request->string('email')->value(),
        ]);
    }
}
