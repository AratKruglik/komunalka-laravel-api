<?php

declare(strict_types=1);

namespace Modules\Billing\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreServiceProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'address_id' => ['required', 'integer', 'exists:addresses,id'],
            'utility_type_id' => ['required', 'integer', 'exists:utility_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'website' => ['nullable', 'string', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'tariffs' => ['sometimes', 'array'],
            'tariffs.*.utility_type_id' => ['required_with:tariffs', 'integer', 'exists:utility_types,id'],
            'tariffs.*.currency_id' => ['required_with:tariffs', 'integer', 'exists:currencies,id'],
            'tariffs.*.name' => ['required_with:tariffs', 'string', 'max:255'],
            'tariffs.*.base_rate' => ['required_with:tariffs', 'numeric', 'min:0'],
            'tariffs.*.service_fee' => ['sometimes', 'numeric', 'min:0'],
            'tariffs.*.effective_from' => ['required_with:tariffs', 'date'],
            'tariffs.*.effective_to' => ['nullable', 'date', 'after_or_equal:tariffs.*.effective_from'],
            'tariffs.*.notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'address_id.required' => 'Адреса є обов\'язковою.',
            'address_id.exists' => 'Обрана адреса не існує.',
            'utility_type_id.required' => 'Тип послуги є обов\'язковим.',
            'utility_type_id.exists' => 'Обраний тип послуги не існує.',
            'name.required' => 'Назва є обов\'язковою.',
            'name.max' => 'Назва не може перевищувати 255 символів.',
            'email.email' => 'Електронна пошта має бути дійсною адресою.',
            'website.url' => 'Веб-сайт має бути дійсною URL-адресою.',
            'tariffs.*.utility_type_id.required_with' => 'Тип послуги тарифу є обов\'язковим.',
            'tariffs.*.utility_type_id.exists' => 'Обраний тип послуги тарифу не існує.',
            'tariffs.*.currency_id.required_with' => 'Валюта тарифу є обов\'язковою.',
            'tariffs.*.currency_id.exists' => 'Обрана валюта не існує.',
            'tariffs.*.name.required_with' => 'Назва тарифу є обов\'язковою.',
            'tariffs.*.base_rate.required_with' => 'Базова ставка є обов\'язковою.',
            'tariffs.*.base_rate.min' => 'Базова ставка не може бути від\'ємною.',
            'tariffs.*.service_fee.min' => 'Плата за обслуговування не може бути від\'ємною.',
            'tariffs.*.effective_from.required_with' => 'Дата початку дії є обов\'язковою.',
            'tariffs.*.effective_to.after_or_equal' => 'Дата закінчення дії має бути не раніше дати початку.',
        ];
    }
}
