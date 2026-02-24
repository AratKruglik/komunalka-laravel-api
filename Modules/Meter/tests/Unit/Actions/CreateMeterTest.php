<?php

declare(strict_types=1);

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\CreateMeter;
use Modules\Meter\DTOs\CreateMeterData;
use Modules\Meter\Models\Meter;
use Modules\Shared\Models\UtilityType;
use Symfony\Component\HttpKernel\Exception\HttpException;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->address = Address::factory()->create();
    $this->user->addresses()->attach($this->address->id, ['is_primary' => true]);
    $this->utilityType = UtilityType::factory()->create();
});

it('creates a meter', function () {
    $data = new CreateMeterData(
        addressId: $this->address->id,
        utilityTypeId: $this->utilityType->id,
        serviceProviderId: null,
        serialNumber: 'M-TEST-001',
        name: 'Лічильник газу',
        description: null,
        modelName: null,
        location: null,
        installationDate: null,
        initialReading: 0.0,
        notes: null,
        isActive: true,
    );

    $meter = app(CreateMeter::class)->handle($this->user->id, $data);

    expect($meter)->toBeInstanceOf(Meter::class)
        ->and($meter->name)->toBe('Лічильник газу')
        ->and($meter->serial_number)->toBe('M-TEST-001')
        ->and($meter->address_id)->toBe($this->address->id);

    $this->assertDatabaseHas('meters', ['serial_number' => 'M-TEST-001', 'name' => 'Лічильник газу']);
});

it('aborts for non-owned address', function () {
    $otherAddress = Address::factory()->create();

    $data = new CreateMeterData(
        addressId: $otherAddress->id,
        utilityTypeId: $this->utilityType->id,
        serviceProviderId: null,
        serialNumber: 'M-TEST-002',
        name: 'Unauthorized Meter',
        description: null,
        modelName: null,
        location: null,
        installationDate: null,
        initialReading: 0.0,
        notes: null,
        isActive: true,
    );

    app(CreateMeter::class)->handle($this->user->id, $data);
})->throws(HttpException::class);
