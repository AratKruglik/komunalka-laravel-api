<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetMeter
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $meterId): Meter
    {
        $meter = $this->meterRepository->findWithRelations($meterId);

        abort_if($meter === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $meter->address_id), Response::HTTP_NOT_FOUND);

        return $meter;
    }
}
