<?php

declare(strict_types=1);

namespace Modules\Address\Http\Resources;

use App\Http\Resources\InertiaJsonApiResource;
use Modules\Address\Models\AddressType;

/** @mixin AddressType */
class AddressTypeResource extends InertiaJsonApiResource
{
    /** @var list<string> */
    public $attributes = [
        'name',
        'description',
        'icon',
    ];
}
