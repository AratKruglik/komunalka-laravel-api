<?php

declare(strict_types=1);

use Modules\Shared\Actions\UpdateCurrency;
use Modules\Shared\DTOs\UpdateCurrencyData;
use Modules\Shared\Models\Currency;

it('updates a currency from DTO', function () {
    $currency = Currency::factory()->create(['code' => 'USD', 'name' => 'US Dollar', 'symbol' => '$']);
    $data = new UpdateCurrencyData(code: 'EUR', name: 'Euro', symbol: '€');

    $updated = app(UpdateCurrency::class)->handle($currency, $data);

    expect($updated->code)->toBe('EUR')
        ->and($updated->name)->toBe('Euro')
        ->and($updated->symbol)->toBe('€');
});
