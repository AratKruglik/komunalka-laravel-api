<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Auth\Models\User;

class DestroyUserAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        if ($this->hasPassword()) {
            return [
                'password' => ['required', 'string', 'current_password:web'],
            ];
        }

        return [
            'confirmation' => ['required', 'string'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        if ($this->hasPassword()) {
            return [
                'password.required' => 'Пароль є обов\'язковим для видалення акаунту.',
                'password.current_password' => 'Невірний пароль.',
            ];
        }

        return [
            'confirmation.required' => 'Введіть ваш email або ім\'я користувача для підтвердження.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        if ($this->hasPassword()) {
            return;
        }

        $validator->after(function (Validator $validator): void {
            /** @var User $user */
            $user = $this->user();
            $confirmation = trim((string) $this->input('confirmation'));

            if ($confirmation === '') {
                return;
            }

            if ($confirmation !== $user->email && $confirmation !== $user->getAttribute('username')) {
                $validator->errors()->add(
                    'confirmation',
                    'Введене значення не збігається з вашим email або іменем користувача.',
                );
            }
        });
    }

    private function hasPassword(): bool
    {
        /** @var User $user */
        $user = $this->user();

        return $user->getAttribute('password') !== null;
    }
}
