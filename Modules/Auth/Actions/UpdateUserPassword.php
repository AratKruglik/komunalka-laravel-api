<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Auth\Models\User;

class UpdateUserPassword
{
    use AsAction;

    public function handle(User $user, string $newPassword): void
    {
        $user->update([
            'password' => Hash::make($newPassword),
        ]);
    }

    public function asController(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Поточний пароль є обов\'язковим.',
            'current_password.current_password' => 'Поточний пароль невірний.',
            'new_password.required' => 'Новий пароль є обов\'язковим.',
            'new_password.min' => 'Новий пароль повинен містити щонайменше 8 символів.',
            'new_password.confirmed' => 'Підтвердження нового паролю не збігається.',
        ]);

        /** @var User $user */
        $user = $request->user();

        $this->handle($user, $validated['new_password']);

        return back()->with('success', 'Пароль успішно змінено.');
    }
}
