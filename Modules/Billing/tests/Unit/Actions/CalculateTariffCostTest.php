<?php

declare(strict_types=1);

use Modules\Billing\Actions\CalculateTariffCost;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\Currency;

it('calculates total cost correctly with bcmath precision', function () {
    $currency = Currency::factory()->create(['code' => 'UAH', 'symbol' => '₴']);
    $tariff = Tariff::factory()->create([
        'base_rate' => '2.6880',
        'service_fee' => '23.5400',
        'currency_id' => $currency->id,
    ]);
    $tariff->load('currency');

    $result = app(CalculateTariffCost::class)->handle(
        tariff: $tariff,
        consumption: '150.0000',
        meterId: 1,
        meterName: 'Лічильник газу',
        unit: 'm³',
    );

    expect($result->totalCost)->toBe('426.7400')
        ->and($result->baseRate)->toBe('2.6880')
        ->and($result->serviceFee)->toBe('23.5400')
        ->and($result->consumption)->toBe('150.0000')
        ->and($result->currencyCode)->toBe('UAH')
        ->and($result->currencySymbol)->toBe('₴')
        ->and($result->meterId)->toBe(1)
        ->and($result->meterName)->toBe('Лічильник газу')
        ->and($result->unit)->toBe('m³');
});

it('handles zero consumption', function () {
    $currency = Currency::factory()->create(['code' => 'UAH', 'symbol' => '₴']);
    $tariff = Tariff::factory()->create([
        'base_rate' => '5.0000',
        'service_fee' => '10.0000',
        'currency_id' => $currency->id,
    ]);
    $tariff->load('currency');

    $result = app(CalculateTariffCost::class)->handle(
        tariff: $tariff,
        consumption: '0',
        meterId: 2,
        meterName: 'Лічильник води',
        unit: 'm³',
    );

    expect($result->totalCost)->toBe('10.0000');
});

it('handles zero service fee', function () {
    $currency = Currency::factory()->create(['code' => 'EUR', 'symbol' => '€']);
    $tariff = Tariff::factory()->create([
        'base_rate' => '1.6800',
        'service_fee' => '0.0000',
        'currency_id' => $currency->id,
    ]);
    $tariff->load('currency');

    $result = app(CalculateTariffCost::class)->handle(
        tariff: $tariff,
        consumption: '250.5000',
        meterId: 3,
        meterName: 'Електролічильник',
        unit: 'kWh',
    );

    expect($result->totalCost)->toBe('420.8400');
});
