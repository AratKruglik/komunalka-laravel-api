<?php

declare(strict_types=1);

use Modules\Address\Models\AddressType;
use Modules\Address\Models\Region;
use Modules\Auth\Models\User;
use Modules\Shared\Models\Currency;
use Modules\Shared\Models\UtilityType;

describe('E2E: address -> provider -> meter -> readings -> export', function () {
    it('completes full meter reading lifecycle with export', function () {
        $user = User::factory()->create();
        $region = Region::factory()->create();
        $addressType = AddressType::factory()->create();
        $utilityType = UtilityType::factory()->create();
        $currency = Currency::factory()->create();

        $addressResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.address.store'), [
                'region_id' => $region->id,
                'address_type_id' => $addressType->id,
                'city' => 'Київ',
                'street' => 'Хрещатик',
                'building_number' => '1',
                'is_primary' => true,
            ])->assertSuccessful();

        $addressId = $addressResponse->json('data.id');

        $providerResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.service-providers.store'), [
                'address_id' => $addressId,
                'utility_type_id' => $utilityType->id,
                'name' => 'Київенерго',
                'account_number' => 'ACC-001',
                'tariffs' => [
                    [
                        'name' => 'Тариф E2E',
                        'utility_type_id' => $utilityType->id,
                        'currency_id' => $currency->id,
                        'base_rate' => '2.5000',
                        'service_fee' => '10.0000',
                        'effective_from' => '2026-01-01',
                        'effective_to' => '2026-12-31',
                    ],
                ],
            ])->assertSuccessful();

        $providerId = $providerResponse->json('data.id');

        $meterResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.meter.store'), [
                'address_id' => $addressId,
                'utility_type_id' => $utilityType->id,
                'service_provider_id' => $providerId,
                'serial_number' => 'E2E-METER-001',
                'name' => 'Лічильник E2E',
                'initial_reading' => 100,
                'is_active' => true,
            ])->assertCreated();

        $meterId = $meterResponse->json('data.id');

        $firstReadingResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterId,
                        'reading_value' => 200,
                        'reading_date' => '2026-02-01',
                    ],
                ],
            ])->assertCreated();

        expect($firstReadingResponse->json('data.readings.0.consumption'))->toBe(100);
        expect($firstReadingResponse->json('data.tariff_calculations'))->not->toBeEmpty();

        $secondReadingResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.meter-readings.store'), [
                'readings' => [
                    [
                        'meter_id' => $meterId,
                        'reading_value' => 350,
                        'reading_date' => '2026-03-01',
                    ],
                ],
            ])->assertCreated();

        expect($secondReadingResponse->json('data.readings.0.consumption'))->toBe(150);

        $this->actingAs($user, 'api')
            ->getJson(route('api.meter-readings.by-address', ['addressId' => $addressId]))
            ->assertSuccessful()
            ->assertJsonCount(2, 'data');

        $exportResponse = $this->actingAs($user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$addressId],
                'from_date' => '2026-01-01',
                'to_date' => '2026-12-31',
                'format' => 'csv',
            ])->assertSuccessful();

        $csvContent = $exportResponse->getContent();
        $lines = array_filter(explode("\n", trim($csvContent)));
        expect($lines)->toHaveCount(3);
    });
});
