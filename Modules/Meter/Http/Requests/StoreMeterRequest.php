<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterRequest extends FormRequest
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
            'service_provider_id' => ['nullable', 'integer', 'exists:service_providers,id'],
            'serial_number' => ['required', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:255'],
            'model_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'installation_date' => ['nullable', 'date'],
            'initial_reading' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'mimes:jpeg,png,gif,heic,heif', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'address_id.required' => 'Адреса є обов\'язковою.',
            'address_id.integer' => 'Ідентифікатор адреси має бути цілим числом.',
            'address_id.exists' => 'Обрана адреса не існує.',
            'utility_type_id.required' => 'Тип послуги є обов\'язковим.',
            'utility_type_id.integer' => 'Ідентифікатор типу послуги має бути цілим числом.',
            'utility_type_id.exists' => 'Обраний тип послуги не існує.',
            'service_provider_id.integer' => 'Ідентифікатор постачальника послуг має бути цілим числом.',
            'service_provider_id.exists' => 'Обраний постачальник послуг не існує.',
            'serial_number.required' => 'Серійний номер є обов\'язковим.',
            'serial_number.max' => 'Серійний номер не може перевищувати 255 символів.',
            'name.required' => 'Назва є обов\'язковою.',
            'name.max' => 'Назва не може перевищувати 255 символів.',
            'description.max' => 'Опис не може перевищувати 255 символів.',
            'model_name.max' => 'Назва моделі не може перевищувати 255 символів.',
            'location.max' => 'Місцезнаходження не може перевищувати 255 символів.',
            'installation_date.date' => 'Дата встановлення має бути дійсною датою.',
            'initial_reading.required' => 'Початкове показання є обов\'язковим.',
            'initial_reading.numeric' => 'Початкове показання має бути числом.',
            'initial_reading.min' => 'Початкове показання не може бути від\'ємним.',
            'is_active.boolean' => 'Поле активності має бути булевим значенням.',
            'photo.mimes' => 'Дозволені формати: JPEG, PNG, GIF, HEIC, HEIF.',
            'photo.max' => 'Розмір фото не може перевищувати 10 МБ.',
        ];
    }
}
