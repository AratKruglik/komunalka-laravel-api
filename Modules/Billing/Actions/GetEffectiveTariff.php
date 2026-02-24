<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Carbon\Carbon;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\Models\Tariff;
use Modules\Billing\Repositories\Contracts\TariffRepositoryInterface;

class GetEffectiveTariff
{
    use AsAction;

    public function __construct(private TariffRepositoryInterface $repository) {}

    public function handle(int $serviceProviderId, int $utilityTypeId, Carbon $date): ?Tariff
    {
        return $this->repository->getEffectiveForUtilityType($serviceProviderId, $utilityTypeId, $date);
    }
}
