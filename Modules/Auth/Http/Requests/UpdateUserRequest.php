<?php

declare(strict_types=1);

namespace Modules\Auth\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'avatar' => ['sometimes', 'image', 'mimes:jpeg,png,gif,heic,heif', 'max:2048'],
            'current_password' => ['required_with:new_password', 'current_password:api'],
            'new_password' => ['sometimes', 'string', 'min:8', 'confirmed'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'first_name.string' => 'Імʼя повинно бути рядком.',
            'last_name.string' => 'Прізвище повинно бути рядком.',
            'phone_number.string' => 'Номер телефону повинен бути рядком.',
            'avatar.image' => 'Аватар повинен бути зображенням.',
            'avatar.mimes' => 'Аватар повинен бути у форматі: jpeg, png, gif, heic або heif.',
            'avatar.max' => 'Розмір аватару не повинен перевищувати 2 МБ.',
            'current_password.required_with' => 'Поточний пароль є обовʼязковим для зміни пароля.',
            'current_password.current_password' => 'Поточний пароль невірний.',
            'new_password.min' => 'Новий пароль повинен містити щонайменше 8 символів.',
            'new_password.confirmed' => 'Підтвердження нового паролю не збігається.',
        ];
    }
}
