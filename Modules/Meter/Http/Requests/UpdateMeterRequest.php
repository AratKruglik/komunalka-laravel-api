<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMeterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'utility_type_id' => ['sometimes', 'integer', 'exists:utility_types,id'],
            'service_provider_id' => ['sometimes', 'nullable', 'integer', 'exists:service_providers,id'],
            'serial_number' => ['sometimes', 'string', 'max:255'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'model_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'location' => ['sometimes', 'nullable', 'string', 'max:255'],
            'installation_date' => ['sometimes', 'nullable', 'date'],
            'initial_reading' => ['sometimes', 'numeric', 'min:0'],
            'notes' => ['sometimes', 'nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
            'photo' => ['sometimes', 'nullable', 'mimes:jpeg,png,gif,heic,heif', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'utility_type_id.integer' => 'Ідентифікатор типу послуги має бути цілим числом.',
            'utility_type_id.exists' => 'Обраний тип послуги не існує.',
            'service_provider_id.integer' => 'Ідентифікатор постачальника послуг має бути цілим числом.',
            'service_provider_id.exists' => 'Обраний постачальник послуг не існує.',
            'serial_number.max' => 'Серійний номер не може перевищувати 255 символів.',
            'name.max' => 'Назва не може перевищувати 255 символів.',
            'description.max' => 'Опис не може перевищувати 255 символів.',
            'model_name.max' => 'Назва моделі не може перевищувати 255 символів.',
            'location.max' => 'Місцезнаходження не може перевищувати 255 символів.',
            'installation_date.date' => 'Дата встановлення має бути дійсною датою.',
            'initial_reading.numeric' => 'Початкове показання має бути числом.',
            'initial_reading.min' => 'Початкове показання не може бути від\'ємним.',
            'is_active.boolean' => 'Поле активності має бути булевим значенням.',
            'photo.mimes' => 'Дозволені формати: JPEG, PNG, GIF, HEIC, HEIF.',
            'photo.max' => 'Розмір фото не може перевищувати 10 МБ.',
        ];
    }
}
