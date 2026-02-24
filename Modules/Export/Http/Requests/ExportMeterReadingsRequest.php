<?php

declare(strict_types=1);

namespace Modules\Export\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportMeterReadingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'address_ids' => ['required', 'array', 'min:1'],
            'address_ids.*' => ['integer', 'exists:addresses,id'],
            'from_date' => ['required', 'date', 'before_or_equal:to_date'],
            'to_date' => ['required', 'date'],
            'format' => ['required', 'in:csv,pdf'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'address_ids.required' => 'Список адрес є обов\'язковим.',
            'address_ids.array' => 'Список адрес має бути масивом.',
            'address_ids.min' => 'Необхідно вказати хоча б одну адресу.',
            'address_ids.*.integer' => 'Ідентифікатор адреси має бути цілим числом.',
            'address_ids.*.exists' => 'Вказана адреса не існує.',
            'from_date.required' => 'Дата початку є обов\'язковою.',
            'from_date.date' => 'Дата початку має бути коректною датою.',
            'from_date.before_or_equal' => 'Дата початку має бути не пізніше дати завершення.',
            'to_date.required' => 'Дата завершення є обов\'язковою.',
            'to_date.date' => 'Дата завершення має бути коректною датою.',
            'format.required' => 'Формат експорту є обов\'язковим.',
            'format.in' => 'Формат експорту має бути одним із: csv, pdf.',
        ];
    }
}
