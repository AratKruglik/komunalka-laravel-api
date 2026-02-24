<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'service_counter_id' => ['required', 'integer', 'exists:service_counters,id'],
            'value' => ['required', 'numeric', 'min:0'],
            'image' => ['nullable', 'image', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'service_counter_id.required' => 'Ідентифікатор лічильника послуги є обов\'язковим.',
            'service_counter_id.integer' => 'Ідентифікатор лічильника послуги має бути цілим числом.',
            'service_counter_id.exists' => 'Обраний лічильник послуги не існує.',
            'value.required' => 'Значення показання є обов\'язковим.',
            'value.numeric' => 'Значення показання має бути числом.',
            'value.min' => 'Значення показання не може бути від\'ємним.',
            'image.image' => 'Файл має бути зображенням.',
            'image.max' => 'Розмір зображення не може перевищувати 10 МБ.',
        ];
    }
}
