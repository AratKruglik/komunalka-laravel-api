<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories;

use App\Repositories\EloquentRepository;
use Illuminate\Database\Eloquent\Collection;
use Modules\Shared\Models\UtilityType;
use Modules\Shared\Repositories\Contracts\UtilityTypeRepositoryInterface;

/** @extends EloquentRepository<UtilityType> */
class UtilityTypeRepository extends EloquentRepository implements UtilityTypeRepositoryInterface
{
    public function __construct(UtilityType $model)
    {
        parent::__construct($model);
    }

    public function findBySlug(string $slug): ?UtilityType
    {
        return $this->newQuery()->where('slug', $slug)->first();
    }

    public function getActive(): Collection
    {
        return $this->newQuery()->active()->get();
    }
}
