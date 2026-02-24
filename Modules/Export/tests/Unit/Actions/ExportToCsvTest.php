<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Collection;
use Modules\Address\Models\Address;
use Modules\Export\Actions\ExportToCsv;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

it('generates valid CSV with correct headers', function () {
    $address = Address::factory()->create([
        'city' => 'Київ',
        'street' => 'Хрещатик',
        'building_number' => '1',
        'apartment_number' => '42',
    ]);

    $utilityType = UtilityType::factory()->create(['display_name' => 'Електрика']);

    $meter = Meter::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $utilityType->getKey(),
        'name' => 'Лічильник електрики',
    ]);

    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->getKey(),
        'reading_value' => 250.50,
        'reading_date' => '2026-01-15',
        'previous_reading_value' => 200.00,
        'consumption' => 50.50,
        'notes' => 'Замітка',
    ]);

    $readings = new Collection([$reading->load('meter.address', 'meter.utilityType')]);

    $csv = app(ExportToCsv::class)->handle($readings);

    $bom = "\xEF\xBB\xBF";
    expect($csv)->toStartWith($bom);

    $lines = array_filter(explode("\n", trim($csv)));
    expect($lines)->toHaveCount(2);

    $headerLine = substr($lines[0], strlen($bom));
    expect($headerLine)->toContain('Дата')
        ->toContain('Адреса')
        ->toContain('Лічильник')
        ->toContain('Тип послуги')
        ->toContain('Попереднє значення')
        ->toContain('Поточне значення')
        ->toContain('Споживання')
        ->toContain('Примітки');

    $parsed = str_getcsv($lines[1]);
    expect($parsed[0])->toBe('15.01.2026')
        ->and($parsed[1])->toContain('Київ')
        ->and($parsed[1])->toContain('Хрещатик')
        ->and($parsed[1])->toContain('кв. 42')
        ->and($parsed[2])->toBe('Лічильник електрики')
        ->and($parsed[4])->toBe('200.00')
        ->and($parsed[5])->toBe('250.50')
        ->and($parsed[6])->toBe('50.50')
        ->and($parsed[7])->toBe('Замітка');
});

it('contains all 8 columns in header', function () {
    $readings = new Collection;

    $csv = app(ExportToCsv::class)->handle($readings);

    $bom = "\xEF\xBB\xBF";
    $headerLine = substr(explode("\n", $csv)[0], strlen($bom));
    $headers = str_getcsv($headerLine);

    expect($headers)->toHaveCount(8)
        ->and($headers[0])->toBe('Дата')
        ->and($headers[1])->toBe('Адреса')
        ->and($headers[2])->toBe('Лічильник')
        ->and($headers[3])->toBe('Тип послуги')
        ->and($headers[4])->toBe('Попереднє значення')
        ->and($headers[5])->toBe('Поточне значення')
        ->and($headers[6])->toBe('Споживання')
        ->and($headers[7])->toBe('Примітки');
});

it('returns only headers for empty collection', function () {
    $readings = new Collection;

    $csv = app(ExportToCsv::class)->handle($readings);

    $lines = array_filter(explode("\n", trim($csv)));
    expect($lines)->toHaveCount(1);
});

it('escapes special characters in CSV fields', function () {
    $address = Address::factory()->create([
        'city' => 'Київ',
        'street' => 'вул. "Шевченка"',
        'building_number' => '10',
        'apartment_number' => null,
    ]);

    $utilityType = UtilityType::factory()->create();

    $meter = Meter::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $utilityType->getKey(),
        'name' => 'Газ, основний',
    ]);

    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->getKey(),
        'reading_value' => 100.00,
        'reading_date' => '2026-02-01',
        'previous_reading_value' => 50.00,
        'consumption' => 50.00,
        'notes' => 'Замітка з "лапками", та комою',
    ]);

    $readings = new Collection([$reading->load('meter.address', 'meter.utilityType')]);

    $csv = app(ExportToCsv::class)->handle($readings);

    $bom = "\xEF\xBB\xBF";
    $dataLine = explode("\n", trim($csv))[1];
    $parsed = str_getcsv($dataLine);

    expect($parsed)->toHaveCount(8)
        ->and($parsed[1])->toContain('вул. "Шевченка"')
        ->and($parsed[2])->toBe('Газ, основний')
        ->and($parsed[7])->toBe('Замітка з "лапками", та комою');
});

it('formats address without apartment number', function () {
    $address = Address::factory()->create([
        'city' => 'Львів',
        'street' => 'Франка',
        'building_number' => '5',
        'apartment_number' => null,
    ]);

    $utilityType = UtilityType::factory()->create();

    $meter = Meter::factory()->create([
        'address_id' => $address->getKey(),
        'utility_type_id' => $utilityType->getKey(),
    ]);

    $reading = MeterReading::factory()->create([
        'meter_id' => $meter->getKey(),
        'reading_date' => '2026-01-01',
    ]);

    $readings = new Collection([$reading->load('meter.address', 'meter.utilityType')]);

    $csv = app(ExportToCsv::class)->handle($readings);

    expect($csv)->toContain('Львів, Франка, 5')
        ->not->toContain('кв.');
});
