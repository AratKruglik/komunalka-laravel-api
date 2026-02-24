<?php

declare(strict_types=1);

namespace Modules\Address\Repositories;

use App\Repositories\EloquentRepository;
use Modules\Address\Models\Region;
use Modules\Address\Repositories\Contracts\RegionRepositoryInterface;

/** @extends EloquentRepository<Region> */
class RegionRepository extends EloquentRepository implements RegionRepositoryInterface
{
    public function __construct(Region $model)
    {
        parent::__construct($model);
    }
}
