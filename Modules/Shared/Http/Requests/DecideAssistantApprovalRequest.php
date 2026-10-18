<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Requests;

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
            'action' => ['required', Rule::in(['approve', 'reject', 'edit'])],
            'arguments' => ['exclude_unless:action,edit', 'required_if:action,edit', 'array:reading_value,reading_date,tariff_id', 'min:1'],
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
            'arguments.required_if' => 'Вкажіть, що саме потрібно змінити.',
            'arguments.array' => 'Некоректні дані для зміни.',
            'arguments.min' => 'Вкажіть, що саме потрібно змінити.',
            'arguments.reading_value.*' => 'Вкажіть коректне значення показника (число, не менше нуля).',
            'arguments.reading_date.*' => 'Вкажіть коректну дату показання, не пізніше сьогодні.',
            'arguments.tariff_id.*' => 'Вкажіть коректну тарифну зону.',
        ];
    }
}
