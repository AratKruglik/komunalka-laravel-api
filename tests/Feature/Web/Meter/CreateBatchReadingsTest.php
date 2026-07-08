<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

beforeEach(function (): void {
    $this->withoutVite();
    $this->user = User::factory()->create();

    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);

    $this->utilityType = UtilityType::factory()->create();
    $this->serviceProvider = ServiceProvider::factory()->create([
        'address_id' => $this->address->getKey(),
        'utility_type_id' => $this->utilityType->getKey(),
    ]);

    $this->meter = Meter::factory()->create([
        'address_id' => $this->address->getKey(),
        'utility_type_id' => $this->utilityType->getKey(),
        'service_provider_id' => $this->serviceProvider->getKey(),
        'initial_reading' => 0,
    ]);
});

describe('AC1 — POST /readings з одним показанням зберігає запис у БД', function (): void {
    it('зберігає показання з коректними значеннями для однозонного лічильника', function (): void {
        $currency = Currency::factory()->create();
        $tariff = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 150.5,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff->getKey(),
                    ],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        $reading = MeterReading::query()
            ->where('meter_id', $this->meter->getKey())
            ->first();

        expect($reading)->not->toBeNull()
            ->and((float) $reading->reading_value)->toBe(150.5)
            ->and((float) $reading->previous_reading_value)->toBe(0.0)
            ->and((float) $reading->consumption)->toBe(150.5)
            ->and($reading->tariff_id)->toBe($tariff->getKey());
    });

    it('зберігає показання без тарифу (null tariff_id)', function (): void {
        $meterWithoutProvider = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'service_provider_id' => null,
            'initial_reading' => 100,
        ]);

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterWithoutProvider->getKey(),
                        'reading_value' => 250,
                        'reading_date' => '2026-06-01',
                    ],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        $reading = MeterReading::query()
            ->where('meter_id', $meterWithoutProvider->getKey())
            ->first();

        expect($reading)->not->toBeNull()
            ->and((float) $reading->reading_value)->toBe(250.0)
            ->and((float) $reading->previous_reading_value)->toBe(100.0)
            ->and((float) $reading->consumption)->toBe(150.0)
            ->and($reading->tariff_id)->toBeNull();
    });

    it('використовує попереднє показання як previous_reading_value, якщо вже існує запис', function (): void {
        $currency = Currency::factory()->create();
        $tariff = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'tariff_id' => $tariff->getKey(),
            'reading_value' => 100,
            'reading_date' => '2026-05-01',
        ]);

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 200,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff->getKey(),
                    ],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        $latest = MeterReading::query()
            ->where('meter_id', $this->meter->getKey())
            ->where('tariff_id', $tariff->getKey())
            ->orderBy('reading_date', 'desc')
            ->first();

        expect((float) $latest->previous_reading_value)->toBe(100.0)
            ->and((float) $latest->consumption)->toBe(100.0);
    });
});

describe('AC3 — POST /readings з двозонним лічильником створює 2 записи', function (): void {
    it('створює два рядки у БД з правильними tariff_id', function (): void {
        $currency = Currency::factory()->create();

        $tariff1 = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $tariff2 = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 300,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff1->getKey(),
                    ],
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 100,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff2->getKey(),
                    ],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        expect(MeterReading::query()->where('meter_id', $this->meter->getKey())->count())->toBe(2);

        $row1 = MeterReading::query()
            ->where('meter_id', $this->meter->getKey())
            ->where('tariff_id', $tariff1->getKey())
            ->first();

        $row2 = MeterReading::query()
            ->where('meter_id', $this->meter->getKey())
            ->where('tariff_id', $tariff2->getKey())
            ->first();

        expect($row1)->not->toBeNull()
            ->and($row2)->not->toBeNull()
            ->and((float) $row1->reading_value)->toBe(300.0)
            ->and((float) $row2->reading_value)->toBe(100.0);
    });

    it('per-zone lookup: попереднє тарифу A не блокує зону B з меншим значенням', function (): void {
        $currency = Currency::factory()->create();

        $tariffA = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $tariffB = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'tariff_id' => $tariffA->getKey(),
            'reading_value' => 500,
            'reading_date' => '2026-05-01',
        ]);

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 550,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariffA->getKey(),
                    ],
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 50,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariffB->getKey(),
                    ],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        expect(
            MeterReading::query()
                ->where('meter_id', $this->meter->getKey())
                ->whereIn('tariff_id', [$tariffA->getKey(), $tariffB->getKey()])
                ->orderBy('reading_date', 'desc')
                ->count()
        )->toBe(3);
    });
});

describe('AC4 — POST /readings із значенням < попереднього повертає 422 з ValidationException', function (): void {
    it('повертає 422 з ключем errors.readings.0.reading_value', function (): void {
        $currency = Currency::factory()->create();
        $tariff = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        MeterReading::factory()->create([
            'meter_id' => $this->meter->getKey(),
            'tariff_id' => $tariff->getKey(),
            'reading_value' => 500,
            'reading_date' => '2026-05-01',
        ]);

        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 300,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.reading_value']);

        expect(
            MeterReading::query()
                ->where('meter_id', $this->meter->getKey())
                ->where('reading_value', 300)
                ->exists()
        )->toBeFalse();
    });

    it('повертає 422 коли значення менше initial_reading лічильника (немає попередніх показань)', function (): void {
        $meterWithHighInitial = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'service_provider_id' => null,
            'initial_reading' => 1000,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterWithHighInitial->getKey(),
                        'reading_value' => 500,
                        'reading_date' => '2026-06-01',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.reading_value']);
    });
});

describe('AC5 — POST /readings з невалідним tariff_id (IDOR) повертає 422', function (): void {
    it('повертає 422 з ключем errors.readings.0.tariff_id коли тариф не належить провайдеру лічильника', function (): void {
        $currency = Currency::factory()->create();

        $otherUtilityType = UtilityType::factory()->create();
        $otherProvider = ServiceProvider::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $otherUtilityType->getKey(),
        ]);

        $foreignTariff = Tariff::factory()->create([
            'service_provider_id' => $otherProvider->getKey(),
            'utility_type_id' => $otherUtilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 150,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $foreignTariff->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.tariff_id']);

        expect(
            MeterReading::query()
                ->where('meter_id', $this->meter->getKey())
                ->exists()
        )->toBeFalse();
    });

    it('повертає 422 коли тариф існує але має інший utility_type', function (): void {
        $currency = Currency::factory()->create();
        $otherUtilityType = UtilityType::factory()->create();

        $tariffWrongType = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $otherUtilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => 150,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariffWrongType->getKey(),
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.tariff_id']);
    });
});

describe('AC6 — Фото прикріплюється лише до першого reading при двох зонах', function (): void {
    it('прикріплює фото тільки до першого reading одного лічильника', function (): void {
        Storage::fake('public');

        $currency = Currency::factory()->create();

        $tariff1 = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $tariff2 = Tariff::factory()->create([
            'service_provider_id' => $this->serviceProvider->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'currency_id' => $currency->getKey(),
            'effective_from' => now()->subYear(),
            'effective_to' => null,
        ]);

        $photo = UploadedFile::fake()->image('meter.jpg', 100, 100);
        $meterId = $this->meter->getKey();

        $this->actingAs($this->user)
            ->post(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterId,
                        'reading_value' => 300,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff1->getKey(),
                    ],
                    [
                        'meter_id' => $meterId,
                        'reading_value' => 100,
                        'reading_date' => '2026-06-01',
                        'tariff_id' => $tariff2->getKey(),
                    ],
                ],
                'photos' => [
                    $meterId => [$photo],
                ],
            ])
            ->assertRedirect(route('readings.index'));

        $readings = MeterReading::query()
            ->where('meter_id', $meterId)
            ->orderBy('id')
            ->get();

        expect($readings)->toHaveCount(2);

        $firstReadingMediaCount = $readings->first()->getMedia('photos')->count();
        $secondReadingMediaCount = $readings->last()->getMedia('photos')->count();

        expect($firstReadingMediaCount)->toBe(1)
            ->and($secondReadingMediaCount)->toBe(0);
    });
});

describe('Захист від несанкціонованого доступу', function (): void {
    it('повертає 404 коли лічильник належить іншій адресі', function (): void {
        $otherAddress = Address::factory()->create();
        $meterOnOtherAddress = Meter::factory()->create([
            'address_id' => $otherAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'service_provider_id' => null,
        ]);

        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterOnOtherAddress->getKey(),
                        'reading_value' => 100,
                        'reading_date' => '2026-06-01',
                    ],
                ],
            ])
            ->assertNotFound();
    });

    it('повертає 401 для неавтентифікованого запиту', function (): void {
        $this->postJson(route('readings.store'), [
            'readings' => [
                [
                    'meter_id' => $this->meter->getKey(),
                    'reading_value' => 100,
                    'reading_date' => '2026-06-01',
                ],
            ],
        ])->assertUnauthorized();
    });
});

describe('Валідація вхідних даних (Form Request)', function (): void {
    it('повертає 422 якщо readings відсутній', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings']);
    });

    it('повертає 422 якщо readings порожній масив', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('readings.store'), ['readings' => []])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings']);
    });

    it('повертає 422 якщо meter_id не існує', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => 99999,
                        'reading_value' => 100,
                        'reading_date' => '2026-06-01',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.meter_id']);
    });

    it('повертає 422 якщо reading_value від\'ємне', function (): void {
        $this->actingAs($this->user)
            ->postJson(route('readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $this->meter->getKey(),
                        'reading_value' => -10,
                        'reading_date' => '2026-06-01',
                    ],
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['readings.0.reading_value']);
    });
});
