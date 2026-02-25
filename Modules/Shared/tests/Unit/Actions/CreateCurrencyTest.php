<?php

declare(strict_types=1);

use Modules\Shared\Actions\CreateCurrency;
use Modules\Shared\DTO\CreateCurrencyData;
use Modules\Shared\Models\Currency;

it('creates a currency from DTO', function () {
    $data = new CreateCurrencyData(code: 'USD', name: 'US Dollar', symbol: '$');

    $currency = app(CreateCurrency::class)->handle($data);

    expect($currency)->toBeInstanceOf(Currency::class)
        ->and($currency->code)->toBe('USD')
        ->and($currency->name)->toBe('US Dollar')
        ->and($currency->symbol)->toBe('$');
});
