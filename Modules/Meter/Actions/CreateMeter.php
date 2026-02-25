<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\DTO\CreateMeterData;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class CreateMeter
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, CreateMeterData $data): Meter
    {
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $data->addressId), Response::HTTP_NOT_FOUND);

        /** @var Meter $meter */
        $meter = $this->meterRepository->create([
            'address_id' => $data->addressId,
            'utility_type_id' => $data->utilityTypeId,
            'service_provider_id' => $data->serviceProviderId,
            'serial_number' => $data->serialNumber,
            'name' => $data->name,
            'description' => $data->description,
            'model_name' => $data->modelName,
            'location' => $data->location,
            'installation_date' => $data->installationDate,
            'initial_reading' => $data->initialReading,
            'notes' => $data->notes,
            'is_active' => $data->isActive,
        ]);

        return $meter->load(['utilityType', 'serviceProvider']);
    }
}
