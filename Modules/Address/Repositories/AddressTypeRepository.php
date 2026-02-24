<?php

declare(strict_types=1);

namespace Modules\Address\Repositories;

use App\Repositories\EloquentRepository;
use Modules\Address\Models\AddressType;
use Modules\Address\Repositories\Contracts\AddressTypeRepositoryInterface;

/** @extends EloquentRepository<AddressType> */
class AddressTypeRepository extends EloquentRepository implements AddressTypeRepositoryInterface
{
    public function __construct(AddressType $model)
    {
        parent::__construct($model);
    }
}
