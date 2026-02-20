<?php

declare(strict_types=1);

namespace Modules\Shared\Repositories\Contracts;

use App\Repositories\Contracts\RepositoryInterface;
use Modules\Shared\Models\Currency;

/** @extends RepositoryInterface<Currency> */
interface CurrencyRepositoryInterface extends RepositoryInterface {}
