<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'string', 'in:user,admin'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'username.required' => 'Імʼя користувача є обовʼязковим.',
            'username.unique' => 'Це імʼя користувача вже зайняте.',
            'first_name.required' => 'Імʼя є обовʼязковим.',
            'last_name.required' => 'Прізвище є обовʼязковим.',
            'email.required' => 'Електронна пошта є обовʼязковою.',
            'email.unique' => 'Обліковий запис з такою електронною поштою вже існує.',
            'password.required' => 'Пароль є обовʼязковим.',
            'password.min' => 'Пароль повинен містити щонайменше 8 символів.',
            'password.confirmed' => 'Підтвердження паролю не збігається.',
            'role.required' => 'Роль є обовʼязковою.',
            'role.in' => 'Роль повинна бути одним із: user, admin.',
        ];
    }
}
