<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Models\MeterReading;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetReadingsByAddress
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private MeterReadingRepositoryInterface $meterReadingRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    /** @return Collection<int, MeterReading> */
    public function handle(int $userId, int $addressId, ?CarbonImmutable $from = null, ?CarbonImmutable $to = null): Collection
    {
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $addressId), Response::HTTP_NOT_FOUND);

        $meterIds = $this->meterRepository->getByAddressId($addressId)->pluck('id')->toArray();

        return $this->meterReadingRepository->getByMeterIds($meterIds, $from, $to);
    }
}
