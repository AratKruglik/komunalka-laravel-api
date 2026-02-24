<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\DTOs\UpdateMeterData;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class UpdateMeter
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $meterId, UpdateMeterData $data): Meter
    {
        $meter = $this->meterRepository->findWithRelations($meterId);

        abort_if($meter === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $meter->address_id), Response::HTTP_NOT_FOUND);

        /** @var Meter $meter */
        $meter = $this->meterRepository->update($meter, $data->toArray());

        return $meter->load(['utilityType', 'serviceProvider']);
    }
}
