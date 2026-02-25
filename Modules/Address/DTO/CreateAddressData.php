<?php

declare(strict_types=1);

namespace Modules\Address\DTO;

use Modules\Address\Http\Requests\StoreAddressRequest;

final readonly class CreateAddressData
{
    public function __construct(
        public int $regionId,
        public int $addressTypeId,
        public string $city,
        public string $street,
        public string $buildingNumber,
        public ?string $apartmentNumber,
        public ?string $zipCode,
        public ?string $notes,
        public bool $isPrimary,
    ) {}

    public static function fromRequest(StoreAddressRequest $request): self
    {
        return new self(
            regionId: (int) $request->validated('region_id'),
            addressTypeId: (int) $request->validated('address_type_id'),
            city: $request->validated('city'),
            street: $request->validated('street'),
            buildingNumber: $request->validated('building_number'),
            apartmentNumber: $request->validated('apartment_number'),
            zipCode: $request->validated('zip_code'),
            notes: $request->validated('notes'),
            isPrimary: (bool) $request->validated('is_primary', false),
        );
    }
}
