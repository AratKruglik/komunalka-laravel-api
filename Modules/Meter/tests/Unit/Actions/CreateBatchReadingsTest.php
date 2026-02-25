<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Actions\CreateBatchReadings;
use Modules\Meter\DTO\BatchReadingData;
use Modules\Meter\DTO\CreateMeterReadingData;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

it('calculates consumption correctly', function () {
    $meter = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 100,
    ]);

    $data = new BatchReadingData(
        readings: [
            new CreateMeterReadingData(
                meterId: $meter->id,
                readingValue: 250,
                readingDate: '2026-02-01',
                notes: null,
                isEstimated: false,
            ),
        ],
        photos: [],
    );

    $result = app(CreateBatchReadings::class)->handle($this->user->id, $data);

    expect($result->readings)->toHaveCount(1)
        ->and($result->readings[0]->consumption)->toBe(150.0)
        ->and($result->readings[0]->previous_reading_value)->toBe(100.0);
});

it('uses initial reading when no previous reading exists', function () {
    $meter = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 50,
    ]);

    $data = new BatchReadingData(
        readings: [
            new CreateMeterReadingData(
                meterId: $meter->id,
                readingValue: 80,
                readingDate: '2026-02-01',
                notes: null,
                isEstimated: false,
            ),
        ],
        photos: [],
    );

    $result = app(CreateBatchReadings::class)->handle($this->user->id, $data);

    expect($result->readings[0]->previous_reading_value)->toBe(50.0)
        ->and($result->readings[0]->consumption)->toBe(30.0);
});

it('aborts when reading value is less than previous', function () {
    $meter = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 0,
    ]);

    MeterReading::factory()->create([
        'meter_id' => $meter->id,
        'reading_value' => 300,
        'reading_date' => '2026-01-01',
    ]);

    $data = new BatchReadingData(
        readings: [
            new CreateMeterReadingData(
                meterId: $meter->id,
                readingValue: 200,
                readingDate: '2026-02-01',
                notes: null,
                isEstimated: false,
            ),
        ],
        photos: [],
    );

    app(CreateBatchReadings::class)->handle($this->user->id, $data);
})->throws(HttpException::class);

it('auto-detects tariff when service provider exists', function () {
    $currency = Currency::factory()->create();
    $serviceProvider = ServiceProvider::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
    ]);

    $tariff = Tariff::factory()->create([
        'service_provider_id' => $serviceProvider->id,
        'utility_type_id' => $this->utilityType->id,
        'currency_id' => $currency->id,
        'base_rate' => '3.0000',
        'service_fee' => '15.0000',
        'effective_from' => '2026-01-01',
        'effective_to' => '2026-12-31',
    ]);

    $meter = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'service_provider_id' => $serviceProvider->id,
        'initial_reading' => 0,
    ]);

    $data = new BatchReadingData(
        readings: [
            new CreateMeterReadingData(
                meterId: $meter->id,
                readingValue: 100,
                readingDate: '2026-06-15',
                notes: null,
                isEstimated: false,
            ),
        ],
        photos: [],
    );

    $result = app(CreateBatchReadings::class)->handle($this->user->id, $data);

    expect($result->tariffCalculations)->toHaveCount(1)
        ->and($result->readings[0]->tariff_id)->toBe($tariff->id);
});

it('wraps in transaction and rolls back on failure', function () {
    $meter1 = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 0,
    ]);

    $meter2 = Meter::factory()->create([
        'address_id' => $this->address->id,
        'utility_type_id' => $this->utilityType->id,
        'initial_reading' => 500,
    ]);

    $data = new BatchReadingData(
        readings: [
            new CreateMeterReadingData(
                meterId: $meter1->id,
                readingValue: 100,
                readingDate: '2026-02-01',
                notes: null,
                isEstimated: false,
            ),
            new CreateMeterReadingData(
                meterId: $meter2->id,
                readingValue: 100,
                readingDate: '2026-02-01',
                notes: null,
                isEstimated: false,
            ),
        ],
        photos: [],
    );

    try {
        app(CreateBatchReadings::class)->handle($this->user->id, $data);
    } catch (HttpException) {
    }

    $this->assertDatabaseCount('meter_readings', 0);
});
