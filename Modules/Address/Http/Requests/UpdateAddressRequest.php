<?php

declare(strict_types=1);

namespace Modules\Address\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'region_id' => ['required', 'integer', 'exists:regions,id'],
            'address_type_id' => ['required', 'integer', 'exists:address_types,id'],
            'city' => ['required', 'string', 'max:255'],
            'street' => ['required', 'string', 'max:255'],
            'building_number' => ['required', 'string', 'max:50'],
            'apartment_number' => ['nullable', 'string', 'max:50'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'region_id.required' => 'Область є обов\'язковою.',
            'region_id.exists' => 'Обрана область не існує.',
            'address_type_id.required' => 'Тип адреси є обов\'язковим.',
            'address_type_id.exists' => 'Обраний тип адреси не існує.',
            'city.required' => 'Місто є обов\'язковим.',
            'street.required' => 'Вулиця є обов\'язковою.',
            'building_number.required' => 'Номер будинку є обов\'язковим.',
        ];
    }
}
