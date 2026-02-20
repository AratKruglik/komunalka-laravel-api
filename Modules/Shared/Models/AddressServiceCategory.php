<?php

declare(strict_types=1);

namespace Modules\Shared\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class AddressServiceCategory extends Pivot
{
    public $incrementing = true;

    protected $table = 'address_service_category';
}
