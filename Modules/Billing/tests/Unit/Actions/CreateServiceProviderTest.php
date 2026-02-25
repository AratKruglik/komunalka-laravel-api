<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Actions\CreateServiceProvider;
use Modules\Billing\DTO\CreateServiceProviderData;
use Modules\Billing\DTO\CreateTariffData;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

it('creates a service provider with tariffs in a transaction', function () {
    $currency = Currency::factory()->create();

    $data = new CreateServiceProviderData(
        addressId: $this->address->id,
        utilityTypeId: $this->utilityType->id,
        name: 'Тест Провайдер',
        description: 'Опис',
        phone: '+380441234567',
        email: 'test@test.ua',
        website: 'https://test.ua',
        isActive: true,
        tariffs: [
            new CreateTariffData(
                utilityTypeId: $this->utilityType->id,
                currencyId: $currency->id,
                name: 'Тариф 1',
                baseRate: '5.5000',
                serviceFee: '20.0000',
                effectiveFrom: '2026-01-01',
                effectiveTo: '2026-12-31',
                notes: 'Примітка',
            ),
        ],
    );

    $provider = app(CreateServiceProvider::class)->handle($this->user->id, $data);

    expect($provider->name)->toBe('Тест Провайдер')
        ->and($provider->tariffs)->toHaveCount(1)
        ->and($provider->tariffs->first()->name)->toBe('Тариф 1');

    $this->assertDatabaseHas('service_providers', ['name' => 'Тест Провайдер']);
    $this->assertDatabaseHas('tariffs', ['name' => 'Тариф 1', 'base_rate' => '5.5000']);
});

it('creates a service provider without tariffs', function () {
    $data = new CreateServiceProviderData(
        addressId: $this->address->id,
        utilityTypeId: $this->utilityType->id,
        name: 'Простий Провайдер',
        description: null,
        phone: null,
        email: null,
        website: null,
        isActive: true,
        tariffs: [],
    );

    $provider = app(CreateServiceProvider::class)->handle($this->user->id, $data);

    expect($provider->name)->toBe('Простий Провайдер')
        ->and($provider->tariffs)->toHaveCount(0);
});

it('aborts when user does not own address', function () {
    $otherAddress = Address::factory()->create();

    $data = new CreateServiceProviderData(
        addressId: $otherAddress->id,
        utilityTypeId: $this->utilityType->id,
        name: 'Unauthorized',
        description: null,
        phone: null,
        email: null,
        website: null,
        isActive: true,
        tariffs: [],
    );

    app(CreateServiceProvider::class)->handle($this->user->id, $data);
})->throws(\Symfony\Component\HttpKernel\Exception\HttpException::class);
