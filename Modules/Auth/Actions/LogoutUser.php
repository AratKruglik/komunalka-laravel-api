<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;

class LogoutUser
{
    use AsAction;

    public function handle(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function asController(Request $request): RedirectResponse
    {
        $this->handle($request);

        return redirect()->route('login');
    }
}
