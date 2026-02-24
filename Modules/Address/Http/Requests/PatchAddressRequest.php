<?php

declare(strict_types=1);

namespace Modules\Address\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PatchAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'region_id' => ['sometimes', 'integer', 'exists:regions,id'],
            'address_type_id' => ['sometimes', 'integer', 'exists:address_types,id'],
            'city' => ['sometimes', 'string', 'max:255'],
            'street' => ['sometimes', 'string', 'max:255'],
            'building_number' => ['sometimes', 'string', 'max:50'],
            'apartment_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'zip_code' => ['sometimes', 'nullable', 'string', 'max:20'],
            'notes' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'region_id.exists' => 'Обрана область не існує.',
            'address_type_id.exists' => 'Обраний тип адреси не існує.',
        ];
    }
}
