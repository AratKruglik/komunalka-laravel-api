<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Region;
use Modules\Address\Repositories\Contracts\RegionRepositoryInterface;

class GetAllRegions
{
    use AsAction;

    public function __construct(private RegionRepositoryInterface $repository) {}

    /** @return Collection<int, Region> */
    public function handle(): Collection
    {
        return $this->repository->all();
    }
}
