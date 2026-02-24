<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'utility_type_id' => ['required', 'integer', 'exists:utility_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'utility_type_id.required' => 'Тип послуги є обов\'язковим.',
            'utility_type_id.exists' => 'Обраний тип послуги не існує.',
            'name.required' => 'Назва є обов\'язковою.',
            'name.max' => 'Назва не може перевищувати 255 символів.',
            'email.email' => 'Електронна пошта має бути дійсною адресою.',
            'website.url' => 'Веб-сайт має бути дійсною URL-адресою.',
        ];
    }
}
