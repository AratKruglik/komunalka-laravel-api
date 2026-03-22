<?php

declare(strict_types=1);

namespace Modules\Address\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Modules\Address\Models\Region;

/** @mixin Region */
class RegionResource extends InertiaJsonApiResource
{
    /** @var list<string> */
    public $attributes = [
        'name',
    ];
}
