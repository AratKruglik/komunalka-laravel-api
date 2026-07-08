<?php

declare(strict_types=1);

namespace Modules\Meter\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BatchMeterReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'readings' => ['required', 'array', 'min:1'],
            'readings.*.meter_id' => ['required', 'integer', 'exists:meters,id'],
            'readings.*.reading_value' => ['required', 'numeric', 'min:0'],
            'readings.*.reading_date' => ['required', 'date'],
            'readings.*.tariff_id' => ['nullable', 'integer', 'exists:tariffs,id'],
            'readings.*.notes' => ['nullable', 'string'],
            'readings.*.is_estimated' => ['sometimes', 'boolean'],
            'photos' => ['sometimes', 'array'],
            'photos.*' => ['sometimes', 'array'],
            'photos.*.*' => ['image', 'mimes:jpg,jpeg,png,webp,heic,heif', 'max:10240'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'readings.required' => 'Список показань є обов\'язковим.',
            'readings.array' => 'Показання мають бути масивом.',
            'readings.min' => 'Необхідно надати хоча б одне показання.',
            'readings.*.meter_id.required' => 'Ідентифікатор лічильника є обов\'язковим.',
            'readings.*.meter_id.integer' => 'Ідентифікатор лічильника має бути цілим числом.',
            'readings.*.meter_id.exists' => 'Обраний лічильник не існує.',
            'readings.*.reading_value.required' => 'Значення показання є обов\'язковим.',
            'readings.*.reading_value.numeric' => 'Значення показання має бути числом.',
            'readings.*.reading_value.min' => 'Значення показання не може бути від\'ємним.',
            'readings.*.reading_date.required' => 'Дата показання є обов\'язковою.',
            'readings.*.reading_date.date' => 'Дата показання має бути дійсною датою.',
            'readings.*.tariff_id.integer' => 'Ідентифікатор тарифу має бути цілим числом.',
            'readings.*.tariff_id.exists' => 'Обраний тариф не існує.',
            'readings.*.is_estimated.boolean' => 'Поле оцінки має бути булевим значенням.',
            'photos.*.*' => 'Кожен файл фото має бути зображенням.',
            'photos.*.*.image' => 'Кожен файл фото має бути зображенням.',
            'photos.*.*.mimes' => 'Дозволені формати фото: JPEG, PNG, WebP, HEIC, HEIF.',
            'photos.*.*.max' => 'Розмір фото не може перевищувати 10 МБ.',
        ];
    }
}
