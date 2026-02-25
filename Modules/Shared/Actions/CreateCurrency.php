<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\DTO\CreateCurrencyData;
use Modules\Shared\Models\Currency;
use Modules\Shared\Repositories\Contracts\CurrencyRepositoryInterface;

class CreateCurrency
{
    use AsAction;

    public function __construct(private CurrencyRepositoryInterface $repository) {}

    public function handle(CreateCurrencyData $data): Currency
    {
        /** @var Currency */
        return $this->repository->create([
            'code' => $data->code,
            'name' => $data->name,
            'symbol' => $data->symbol,
        ]);
    }
}
