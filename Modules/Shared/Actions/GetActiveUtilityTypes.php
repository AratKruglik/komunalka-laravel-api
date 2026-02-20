<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Models\UtilityType;
use Modules\Shared\Repositories\Contracts\UtilityTypeRepositoryInterface;

class GetActiveUtilityTypes
{
    use AsAction;

    public function __construct(private UtilityTypeRepositoryInterface $repository) {}

    /** @return Collection<int, UtilityType> */
    public function handle(): Collection
    {
        return $this->repository->getActive();
    }
}
