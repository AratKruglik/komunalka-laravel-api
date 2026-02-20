<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\Models\UtilityType;

/**
 * @extends RepositoryInterface<UtilityType>
 */
interface UtilityTypeRepositoryInterface extends RepositoryInterface
{
    public function findBySlug(string $slug): ?UtilityType;

    /** @return Collection<int, UtilityType> */
    public function getActive(): Collection;
}
