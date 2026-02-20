<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\DTOs\UpdateCurrencyData;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

class UpdateCurrency
{
    use AsAction;

    public function __construct(private CurrencyRepositoryInterface $repository) {}

    public function handle(Currency $currency, UpdateCurrencyData $data): Currency
    {
        /** @var Currency */
        return $this->repository->update($currency, [
            'code' => $data->code,
            'name' => $data->name,
            'symbol' => $data->symbol,
        ]);
    }
}
