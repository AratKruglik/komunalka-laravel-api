<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

class GetAllCurrencies
{
    use AsAction;

    public function __construct(private CurrencyRepositoryInterface $repository) {}

    /** @return Collection<int, Currency> */
    public function handle(): Collection
    {
        return $this->repository->all();
    }
}
