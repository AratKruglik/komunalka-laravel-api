<?php

declare(strict_types=1);

namespace Modules\Address\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class UserAddress extends Pivot
{
    public $incrementing = true;

    protected $table = 'address_user';

    public function casts(): array
    {
        return [
            'is_primary' => 'boolean',
        ];
    }
}
