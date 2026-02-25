<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Billing\Actions\GetEffectiveTariff;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->utilityType = UtilityType::factory()->create();
    $this->provider = ServiceProvider::factory()->create([
        'utility_type_id' => $this->utilityType->id,
    ]);
});

it('returns tariff effective at given date', function () {
    $tariff = Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $this->utilityType->id,
        'effective_from' => '2026-01-01',
        'effective_to' => '2026-12-31',
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2026-06-15'),
    );

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($tariff->id);
});

it('returns null when no tariff is effective at given date', function () {
    Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $this->utilityType->id,
        'effective_from' => '2026-01-01',
        'effective_to' => '2026-06-30',
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2026-07-01'),
    );

    expect($result)->toBeNull();
});

it('returns tariff with no end date when effective_to is null', function () {
    $tariff = Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $this->utilityType->id,
        'effective_from' => '2025-01-01',
        'effective_to' => null,
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2030-12-31'),
    );

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($tariff->id);
});

it('returns tariff on boundary date (effective_from)', function () {
    $tariff = Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $this->utilityType->id,
        'effective_from' => '2026-03-01',
        'effective_to' => '2026-03-31',
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2026-03-01'),
    );

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($tariff->id);
});

it('returns tariff on boundary date (effective_to)', function () {
    $tariff = Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $this->utilityType->id,
        'effective_from' => '2026-03-01',
        'effective_to' => '2026-03-31',
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2026-03-31'),
    );

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($tariff->id);
});

it('filters by utility type id', function () {
    $otherUtilityType = UtilityType::factory()->create();

    Tariff::factory()->create([
        'service_provider_id' => $this->provider->id,
        'utility_type_id' => $otherUtilityType->id,
        'effective_from' => '2026-01-01',
        'effective_to' => null,
    ]);

    $result = app(GetEffectiveTariff::class)->handle(
        $this->provider->id,
        $this->utilityType->id,
        CarbonImmutable::parse('2026-06-15'),
    );

    expect($result)->toBeNull();
});
