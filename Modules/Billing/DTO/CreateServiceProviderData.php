<?php

declare(strict_types=1);

namespace Modules\Billing\DTO;

use Modules\Billing\Http\Requests\StoreServiceProviderRequest;

final readonly class CreateServiceProviderData
{
    /**
     * @param  array<int, CreateTariffData>  $tariffs
     */
    public function __construct(
        public int $addressId,
        public int $utilityTypeId,
        public string $name,
        public ?string $description,
        public ?string $phone,
        public ?string $email,
        public ?string $website,
        public bool $isActive,
        public array $tariffs,
    ) {}

    public static function fromRequest(StoreServiceProviderRequest $request): self
    {
        $tariffs = [];
        foreach ($request->validated('tariffs', []) as $tariff) {
            $tariffs[] = new CreateTariffData(
                utilityTypeId: (int) $tariff['utility_type_id'],
                currencyId: (int) $tariff['currency_id'],
                name: $tariff['name'],
                baseRate: (string) $tariff['base_rate'],
                serviceFee: (string) ($tariff['service_fee'] ?? '0'),
                effectiveFrom: $tariff['effective_from'],
                effectiveTo: $tariff['effective_to'] ?? null,
                notes: $tariff['notes'] ?? null,
            );
        }

        return new self(
            addressId: (int) $request->validated('address_id'),
            utilityTypeId: (int) $request->validated('utility_type_id'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            phone: $request->validated('phone'),
            email: $request->validated('email'),
            website: $request->validated('website'),
            isActive: (bool) $request->validated('is_active', true),
            tariffs: $tariffs,
        );
    }
}
