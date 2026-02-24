<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetMetersByAddress
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    /** @return Collection<int, Meter> */
    public function handle(int $userId, int $addressId): Collection
    {
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $addressId), Response::HTTP_NOT_FOUND);

        return $this->meterRepository->getByAddressId($addressId);
    }
}
