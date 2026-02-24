<?php

declare(strict_types=1);

namespace Modules\Address\DTOs;

use Modules\Address\Http\Requests\PatchAddressRequest;

final readonly class PatchAddressData
{
    public function __construct(
        public ?int $regionId,
        public ?int $addressTypeId,
        public ?string $city,
        public ?string $street,
        public ?string $buildingNumber,
        public ?string $apartmentNumber,
        public ?string $zipCode,
        public ?string $notes,
        public ?bool $isPrimary,
    ) {}

    public static function fromRequest(PatchAddressRequest $request): self
    {
        return new self(
            regionId: $request->has('region_id') ? (int) $request->validated('region_id') : null,
            addressTypeId: $request->has('address_type_id') ? (int) $request->validated('address_type_id') : null,
            city: $request->validated('city'),
            street: $request->validated('street'),
            buildingNumber: $request->validated('building_number'),
            apartmentNumber: $request->has('apartment_number') ? $request->validated('apartment_number') : null,
            zipCode: $request->has('zip_code') ? $request->validated('zip_code') : null,
            notes: $request->has('notes') ? $request->validated('notes') : null,
            isPrimary: $request->has('is_primary') ? (bool) $request->validated('is_primary') : null,
        );
    }

    /** @return array<string, mixed> */
    public function toAddressAttributes(): array
    {
        $attributes = [];

        if ($this->regionId !== null) {
            $attributes['region_id'] = $this->regionId;
        }
        if ($this->addressTypeId !== null) {
            $attributes['address_type_id'] = $this->addressTypeId;
        }
        if ($this->city !== null) {
            $attributes['city'] = $this->city;
        }
        if ($this->street !== null) {
            $attributes['street'] = $this->street;
        }
        if ($this->buildingNumber !== null) {
            $attributes['building_number'] = $this->buildingNumber;
        }
        if ($this->apartmentNumber !== null) {
            $attributes['apartment_number'] = $this->apartmentNumber;
        }
        if ($this->zipCode !== null) {
            $attributes['zip_code'] = $this->zipCode;
        }
        if ($this->notes !== null) {
            $attributes['notes'] = $this->notes;
        }

        return $attributes;
    }
}
