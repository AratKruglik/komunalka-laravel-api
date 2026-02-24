<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Export\Actions\ExportToPdf;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Shared\Models\UtilityType;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->getKey(), ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

describe('POST /api/v1/export/meter-readings (CSV)', function () {
    it('exports meter readings as CSV with correct content type', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_value' => 150.50,
            'reading_date' => '2026-01-15',
            'previous_reading_value' => 100.00,
            'consumption' => 50.50,
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ]);

        $response->assertSuccessful();
        expect($response->headers->get('Content-Type'))->toContain('text/csv');
        expect($response->headers->get('Content-Disposition'))->toContain('meter-readings.csv');
    });

    it('returns CSV with correct headers and structure', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
            'name' => 'Газ кухня',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_value' => 200.00,
            'reading_date' => '2026-01-10',
            'previous_reading_value' => 150.00,
            'consumption' => 50.00,
            'notes' => 'Тестова замітка',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ]);

        $content = $response->getContent();
        $lines = array_filter(explode("\n", trim($content)));

        expect($lines)->toHaveCount(2);

        $bom = "\xEF\xBB\xBF";
        $headerLine = str_starts_with($lines[0], $bom)
            ? substr($lines[0], strlen($bom))
            : $lines[0];

        expect($headerLine)->toContain('Дата')
            ->toContain('Адреса')
            ->toContain('Лічильник')
            ->toContain('Тип послуги')
            ->toContain('Попереднє значення')
            ->toContain('Поточне значення')
            ->toContain('Споживання')
            ->toContain('Примітки');

        expect($lines[1])->toContain('10.01.2026')
            ->toContain('Газ кухня')
            ->toContain('Тестова замітка');
    });

    it('exports readings with date range filtering', function () {
        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => '2026-01-15',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => '2026-03-15',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ]);

        $response->assertSuccessful();

        $content = $response->getContent();
        $lines = array_filter(explode("\n", trim($content)));

        expect($lines)->toHaveCount(2);
    });

    it('supports multiple address_ids', function () {
        $secondAddress = Address::factory()->create();
        $this->user->addresses()->attach($secondAddress->getKey(), ['is_primary' => false]);

        $meter1 = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        $meter2 = Meter::factory()->create([
            'address_id' => $secondAddress->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter1->getKey(),
            'reading_date' => '2026-01-10',
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter2->getKey(),
            'reading_date' => '2026-01-20',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey(), $secondAddress->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ]);

        $response->assertSuccessful();

        $content = $response->getContent();
        $lines = array_filter(explode("\n", trim($content)));

        expect($lines)->toHaveCount(3);
    });
});

describe('POST /api/v1/export/meter-readings (PDF)', function () {
    it('exports meter readings as PDF with correct content type', function () {
        $fakePdfContent = '%PDF-1.4 fake content';
        $this->mock(ExportToPdf::class)
            ->shouldReceive('handle')
            ->once()
            ->andReturn($fakePdfContent);

        $meter = Meter::factory()->create([
            'address_id' => $this->address->getKey(),
            'utility_type_id' => $this->utilityType->getKey(),
        ]);

        MeterReading::factory()->create([
            'meter_id' => $meter->getKey(),
            'reading_date' => '2026-01-15',
        ]);

        $response = $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'pdf',
            ]);

        $response->assertSuccessful();
        expect($response->headers->get('Content-Type'))->toContain('application/pdf');
        expect($response->headers->get('Content-Disposition'))->toContain('meter-readings.pdf');
        expect($response->getContent())->toBe($fakePdfContent);
    });
});

describe('POST /api/v1/export/meter-readings (authorization)', function () {
    it('returns 403 for addresses not owned by user', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$otherAddress->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertForbidden();
    });

    it('returns 403 when one of multiple addresses is not owned', function () {
        $otherAddress = Address::factory()->create();

        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey(), $otherAddress->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertForbidden();
    });

    it('returns 401 for unauthenticated requests', function () {
        $this->postJson(route('api.export.meter-readings'), [
            'address_ids' => [$this->address->getKey()],
            'from_date' => '2026-01-01',
            'to_date' => '2026-01-31',
            'format' => 'csv',
        ])->assertUnauthorized();
    });
});

describe('POST /api/v1/export/meter-readings (validation)', function () {
    it('requires address_ids', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_ids');
    });

    it('requires address_ids to be a non-empty array', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_ids');
    });

    it('requires from_date', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from_date');
    });

    it('requires to_date', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('to_date');
    });

    it('requires format', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('format');
    });

    it('rejects invalid format', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'xlsx',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('format');
    });

    it('rejects from_date after to_date', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => '2026-02-01',
                'to_date' => '2026-01-01',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from_date');
    });

    it('rejects non-existent address_ids', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [999999],
                'from_date' => '2026-01-01',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('address_ids.0');
    });

    it('rejects invalid date format', function () {
        $this->actingAs($this->user, 'api')
            ->postJson(route('api.export.meter-readings'), [
                'address_ids' => [$this->address->getKey()],
                'from_date' => 'not-a-date',
                'to_date' => '2026-01-31',
                'format' => 'csv',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('from_date');
    });
});
