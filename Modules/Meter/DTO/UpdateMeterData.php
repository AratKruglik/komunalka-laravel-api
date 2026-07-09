<?php

declare(strict_types=1);

namespace Modules\Meter\DTO;

use Illuminate\Http\UploadedFile;
use Modules\Meter\Http\Requests\UpdateMeterRequest;

final readonly class UpdateMeterData
{
    public function __construct(
        public ?int $utilityTypeId,
        public ?int $serviceProviderId,
        public ?string $serialNumber,
        public ?string $name,
        public ?string $description,
        public ?string $modelName,
        public ?string $location,
        public ?string $installationDate,
        public ?float $initialReading,
        public ?string $notes,
        public ?bool $isActive,
        public ?UploadedFile $photo = null,
    ) {}

    public static function fromRequest(UpdateMeterRequest $request): self
    {
        return new self(
            utilityTypeId: $request->validated('utility_type_id') !== null
                ? (int) $request->validated('utility_type_id')
                : null,
            serviceProviderId: $request->validated('service_provider_id') !== null
                ? (int) $request->validated('service_provider_id')
                : null,
            serialNumber: $request->validated('serial_number'),
            name: $request->validated('name'),
            description: $request->validated('description'),
            modelName: $request->validated('model_name'),
            location: $request->validated('location'),
            installationDate: $request->validated('installation_date'),
            initialReading: $request->validated('initial_reading') !== null
                ? (float) $request->validated('initial_reading')
                : null,
            notes: $request->validated('notes'),
            isActive: $request->validated('is_active') !== null
                ? (bool) $request->validated('is_active')
                : null,
            photo: $request->file('photo'),
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return array_filter([
            'utility_type_id' => $this->utilityTypeId,
            'service_provider_id' => $this->serviceProviderId,
            'serial_number' => $this->serialNumber,
            'name' => $this->name,
            'description' => $this->description,
            'model_name' => $this->modelName,
            'location' => $this->location,
            'installation_date' => $this->installationDate,
            'initial_reading' => $this->initialReading,
            'notes' => $this->notes,
            'is_active' => $this->isActive,
        ], fn ($value) => $value !== null);
    }
}
