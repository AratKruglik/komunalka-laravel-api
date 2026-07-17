<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Http\Requests\DestroyUserAccountRequest;
use Modules\Auth\Models\User;

class DestroyUserAccount
{
    use AsAction;

    public function handle(User $user): void
    {
        DeleteUser::run($user);
    }

    public function asController(DestroyUserAccountRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $this->handle($user);

        return redirect()->route('login')->with('success', 'Ваш акаунт було видалено.');
    }
}
