<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories;

use App\Repositories\EloquentRepository;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

/** @extends EloquentRepository<Currency> */
class CurrencyRepository extends EloquentRepository implements CurrencyRepositoryInterface
{
    public function __construct(Currency $model)
    {
        parent::__construct($model);
    }
}
