<?php

declare(strict_types=1);

namespace Modules\Ai\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DecideAssistantApprovalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, array<int, mixed>> */
    public function rules(): array
    {
        return [
            'conversation_id' => ['required', 'uuid'],
            'approval_id' => ['required', 'string', 'max:255'],
            'version' => ['nullable', 'integer', 'min:0'],
            'action' => ['required', Rule::in(['approve', 'reject', 'edit'])],
            'arguments' => ['exclude_unless:action,edit', 'required_if:action,edit', 'array:reading_value,reading_date,tariff_id,address_id,address,provider,meter', 'min:1'],
            'arguments.address_id' => ['sometimes', 'nullable', 'integer'],
            'arguments.address' => ['sometimes', 'array:region_id,address_type_id,city,street,building_number,apartment_number,zip_code,notes,is_primary'],
            'arguments.provider' => ['sometimes', 'array:utility_type_id,name,description,phone,email,website,tariffs'],
            'arguments.provider.tariffs' => ['sometimes', 'array', 'max:5'],
            'arguments.meter' => ['sometimes', 'array:utility_type_id,service_provider_id,serial_number,name,description,model_name,location,installation_date,initial_reading,notes'],
            'arguments.reading_value' => ['sometimes', 'numeric', 'min:0', 'max:99999999.999'],
            'arguments.reading_date' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:today'],
            'arguments.tariff_id' => ['sometimes', 'nullable', 'integer'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'conversation_id.*' => 'Розмову не знайдено.',
            'approval_id.*' => 'Запит на підтвердження не знайдено.',
            'action.*' => 'Оберіть дію: підтвердити, скасувати або змінити.',
            'version.*' => 'Версія пропозиції некоректна.',
            'arguments.required_if' => 'Вкажіть, що саме потрібно змінити.',
            'arguments.array' => 'Некоректні дані для зміни.',
            'arguments.min' => 'Вкажіть, що саме потрібно змінити.',
            'arguments.reading_value.*' => 'Вкажіть коректне значення показника (число, не менше нуля).',
            'arguments.reading_date.*' => 'Вкажіть коректну дату показання, не пізніше сьогодні.',
            'arguments.tariff_id.*' => 'Вкажіть коректну тарифну зону.',
        ];
    }
}
