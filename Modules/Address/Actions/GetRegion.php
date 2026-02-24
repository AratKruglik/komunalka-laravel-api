<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Region;
use Modules\Address\Repositories\Contracts\RegionRepositoryInterface;

class GetRegion
{
    use AsAction;

    public function __construct(private RegionRepositoryInterface $repository) {}

    public function handle(int $id): Region
    {
        /** @var Region */
        return $this->repository->findOrFail($id);
    }
}
