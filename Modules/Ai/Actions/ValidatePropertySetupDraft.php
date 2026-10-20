<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;

class ValidatePropertySetupDraft
{
    /** @param array<string, mixed> $draft
     * @return array<string, mixed>
     */
    public function handle(User $user, array $draft): array
    {
        $data = Validator::make($draft, [
            'address_id' => ['nullable', 'integer', 'exists:addresses,id'],
            'address' => ['nullable', 'array:region_id,address_type_id,city,street,building_number,apartment_number,zip_code,notes,is_primary'],
            'address.region_id' => ['required_with:address', 'integer', 'exists:regions,id'],
            'address.address_type_id' => ['required_with:address', 'integer', 'exists:address_types,id'],
            'address.city' => ['required_with:address', 'string', 'max:255'],
            'address.street' => ['required_with:address', 'string', 'max:255'],
            'address.building_number' => ['required_with:address', 'string', 'max:50'],
            'address.apartment_number' => ['nullable', 'string', 'max:50'],
            'address.zip_code' => ['nullable', 'string', 'max:20'],
            'address.notes' => ['nullable', 'string', 'max:1000'],
            'address.is_primary' => ['sometimes', 'boolean'],
            'provider' => ['nullable', 'array:utility_type_id,name,description,phone,email,website,tariffs'],
            'provider.utility_type_id' => ['required_with:provider', 'integer', 'exists:utility_types,id'],
            'provider.name' => ['required_with:provider', 'string', 'max:255'],
            'provider.description' => ['nullable', 'string', 'max:1000'],
            'provider.phone' => ['nullable', 'string', 'max:50'],
            'provider.email' => ['nullable', 'email', 'max:255'],
            'provider.website' => ['nullable', 'url', 'max:500'],
            'provider.tariffs' => ['sometimes', 'array', 'max:5'],
            'provider.tariffs.*' => ['array:utility_type_id,currency_id,name,base_rate,service_fee,effective_from,effective_to,notes'],
            'provider.tariffs.*.utility_type_id' => ['required', 'integer', 'exists:utility_types,id'],
            'provider.tariffs.*.currency_id' => ['required', 'integer', 'exists:currencies,id'],
            'provider.tariffs.*.name' => ['required', 'string', 'max:255'],
            'provider.tariffs.*.base_rate' => ['required', 'numeric', 'min:0'],
            'provider.tariffs.*.service_fee' => ['sometimes', 'numeric', 'min:0'],
            'provider.tariffs.*.effective_from' => ['required', 'date_format:Y-m-d'],
            'provider.tariffs.*.effective_to' => ['nullable', 'date_format:Y-m-d'],
            'provider.tariffs.*.notes' => ['nullable', 'string', 'max:1000'],
            'meter' => ['nullable', 'array:utility_type_id,service_provider_id,serial_number,name,description,model_name,location,installation_date,initial_reading,notes'],
            'meter.utility_type_id' => ['required_with:meter', 'integer', 'exists:utility_types,id'],
            'meter.service_provider_id' => ['nullable', 'integer', 'exists:service_providers,id'],
            'meter.serial_number' => ['required_with:meter', 'string', 'max:255'],
            'meter.name' => ['required_with:meter', 'string', 'max:255'],
            'meter.description' => ['nullable', 'string', 'max:255'],
            'meter.model_name' => ['nullable', 'string', 'max:255'],
            'meter.location' => ['nullable', 'string', 'max:255'],
            'meter.installation_date' => ['nullable', 'date'],
            'meter.initial_reading' => ['required_with:meter', 'numeric', 'min:0'],
            'meter.notes' => ['nullable', 'string', 'max:1000'],
        ])->validate();

        if (empty($data['address']) && empty($data['provider']) && empty($data['meter'])) {
            throw ValidationException::withMessages(['draft' => 'Додайте хоча б один об’єкт.']);
        }

        if (! empty($data['address']) && ! empty($data['address_id'])) {
            throw ValidationException::withMessages(['address_id' => 'Оберіть існуючу адресу або створіть нову.']);
        }

        if (empty($data['address']) && (! empty($data['provider']) || ! empty($data['meter'])) && empty($data['address_id'])) {
            throw ValidationException::withMessages(['address_id' => 'Оберіть адресу для провайдера або лічильника.']);
        }

        if (! empty($data['address_id']) && ! $user->addresses()->whereKey($data['address_id'])->exists()) {
            abort(403);
        }

        if (! empty($data['meter']['service_provider_id'])) {
            if (! empty($data['provider'])) {
                throw ValidationException::withMessages(['meter.service_provider_id' => 'Оберіть наявного провайдера або створіть нового.']);
            }
            $provider = ServiceProvider::query()->whereKey($data['meter']['service_provider_id'])->firstOrFail();
            if (empty($data['address_id']) || (int) $provider->getAttribute('address_id') !== $data['address_id']
                || (int) $provider->getAttribute('utility_type_id') !== $data['meter']['utility_type_id']) {
                throw ValidationException::withMessages(['meter.service_provider_id' => 'Провайдер має належати цій адресі та типу послуги.']);
            }
        }

        if (! empty($data['provider']) && ! empty($data['meter'])
            && $data['provider']['utility_type_id'] !== $data['meter']['utility_type_id']) {
            throw ValidationException::withMessages(['meter.utility_type_id' => 'Тип послуги лічильника має збігатися з новим провайдером.']);
        }

        foreach ($data['provider']['tariffs'] ?? [] as $tariff) {
            if ($tariff['utility_type_id'] !== $data['provider']['utility_type_id']
                || (! empty($tariff['effective_to']) && $tariff['effective_to'] < $tariff['effective_from'])) {
                throw ValidationException::withMessages(['provider.tariffs' => 'Тариф має відповідати типу послуги та коректному періоду дії.']);
            }
        }

        return $data;
    }
}
