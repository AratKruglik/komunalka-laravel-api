<?php

declare(strict_types=1);

namespace Modules\Auth\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;

class SendPasswordResetLink
{
    use AsAction;

    public function asController(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ], [
            'email.required' => 'Електронна пошта є обов\'язковою.',
            'email.email' => 'Введіть дійсну адресу електронної пошти.',
        ]);

        $status = Password::sendResetLink(['email' => $validated['email']]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => ['Не вдалося знайти користувача з такою адресою електронної пошти.'],
            ]);
        }

        return back()->with('status', 'Посилання для скидання пароля надіслано на вашу пошту.');
    }
}
