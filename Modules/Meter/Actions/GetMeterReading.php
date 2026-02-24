<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Models\MeterReading;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetMeterReading
{
    use AsAction;

    public function __construct(
        private MeterReadingRepositoryInterface $meterReadingRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $readingId): MeterReading
    {
        $reading = $this->meterReadingRepository->findWithRelations($readingId);

        abort_if($reading === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $reading->meter->address_id), Response::HTTP_NOT_FOUND);

        return $reading;
    }
}
