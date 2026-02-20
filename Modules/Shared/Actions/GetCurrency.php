<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

class GetCurrency
{
    use AsAction;

    public function __construct(private CurrencyRepositoryInterface $repository) {}

    public function handle(int $id): Currency
    {
        /** @var Currency */
        return $this->repository->findOrFail($id);
    }
}
