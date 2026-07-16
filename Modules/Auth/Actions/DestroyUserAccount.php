<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;

class DestroyUserAccount
{
    use AsAction;

    public function handle(User $user): void
    {
        DeleteUser::run($user);
    }

    public function asController(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'current_password:web'],
        ], [
            'password.required' => 'Пароль є обов\'язковим для видалення акаунту.',
            'password.current_password' => 'Невірний пароль.',
        ]);

        /** @var User $user */
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->handle($user);

        return redirect()->route('login')->with('success', 'Ваш акаунт було видалено.');
    }
}
