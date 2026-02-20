<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

class DeleteCurrency
{
    use AsAction;

    public function __construct(private CurrencyRepositoryInterface $repository) {}

    public function handle(Currency $currency): bool
    {
        return $this->repository->delete($currency);
    }
}
