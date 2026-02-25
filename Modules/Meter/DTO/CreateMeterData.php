<?php

declare(strict_types=1);

namespace Modules\Meter\DTO;

use Modules\Meter\Http\Requests\StoreMeterRequest;

final readonly class CreateMeterData
{
    public function __construct(
        public int $addressId,
        public int $utilityTypeId,
        public ?int $serviceProviderId,
        public string $serialNumber,
        public string $name,
        public ?string $description,
        public ?string $modelName,
        public ?string $location,
        public ?string $installationDate,
        public float $initialReading,
        public ?string $notes,
        public bool $isActive,
    ) {}

    public static function fromRequest(StoreMeterRequest $request): self
    {
        return new self(
            addressId: (int) $request->validated('address_id'),
            utilityTypeId: (int) $request->validated('utility_type_id'),
            serviceProviderId: $request->validated('service_provider_id') !== null
                ? (int) $request->validated('service_provider_id')
                : null,
            serialNumber: $request->validated('serial_number'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            modelName: $request->validated('model_name'),
            location: $request->validated('location'),
            installationDate: $request->validated('installation_date'),
            initialReading: (float) $request->validated('initial_reading', 0),
            notes: $request->validated('notes'),
            isActive: (bool) $request->validated('is_active', true),
        );
    }
}
