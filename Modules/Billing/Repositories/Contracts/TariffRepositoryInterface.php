<?php

declare(strict_types=1);

namespace Modules\Billing\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Modules\Billing\Models\Tariff;

/**
 * @extends RepositoryInterface<Tariff>
 */
interface TariffRepositoryInterface extends RepositoryInterface
{
    public function getEffective(int $serviceProviderId, Carbon $date): ?Tariff;

    public function getEffectiveForUtilityType(int $serviceProviderId, int $utilityTypeId, Carbon $date): ?Tariff;

    /** @return Collection<int, Tariff> */
    public function getByServiceProviderId(int $serviceProviderId): Collection;
}
