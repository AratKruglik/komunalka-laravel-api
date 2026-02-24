<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\UserAddress;
use Modules\Meter\Models\Meter;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;

class GetActiveMeters
{
    use AsAction;

    public function __construct(private MeterRepositoryInterface $meterRepository) {}

    /** @return Collection<int, Meter> */
    public function handle(int $userId): Collection
    {
        $addressIds = UserAddress::query()
            ->where('user_id', $userId)
            ->pluck('address_id')
            ->toArray();

        return $this->meterRepository->getActiveByAddressIds($addressIds);
    }
}
