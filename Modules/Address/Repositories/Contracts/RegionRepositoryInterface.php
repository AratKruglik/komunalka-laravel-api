<?php

declare(strict_types=1);

namespace Modules\Address\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Modules\Address\Models\Region;

/** @extends RepositoryInterface<Region> */
interface RegionRepositoryInterface extends RepositoryInterface {}
