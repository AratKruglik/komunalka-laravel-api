<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Meter\Actions\DeleteMeterReading;
use Modules\Meter\Events\MeterReadingsChanged;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Symfony\Component\HttpKernel\Exception\HttpException;

mutates(DeleteMeterReading::class);

it('deletes a reading and dispatches the changed event', function (): void {
    Event::fake([MeterReadingsChanged::class]);
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $meter = Meter::factory()->create(['address_id' => $address->getKey()]);
    $reading = MeterReading::factory()->create(['meter_id' => $meter->getKey()]);

    app(DeleteMeterReading::class)->handle((int) $user->getKey(), (int) $reading->getKey());

    $this->assertDatabaseMissing('meter_readings', ['id' => $reading->getKey()]);
    Event::assertDispatched(MeterReadingsChanged::class, fn (MeterReadingsChanged $e): bool => $e->addressId === $address->getKey()
        && $e->action === 'deleted'
        && $e->readingIds === [$reading->getKey()]
        && $e->meterIds === [$meter->getKey()]);
});

it('aborts and dispatches nothing for a non-owned reading', function (): void {
    Event::fake([MeterReadingsChanged::class]);
    $user = User::factory()->create();
    $reading = MeterReading::factory()->create();

    try {
        app(DeleteMeterReading::class)->handle((int) $user->getKey(), (int) $reading->getKey());
    } catch (HttpException) {
    }

    Event::assertNotDispatched(MeterReadingsChanged::class);
    $this->assertDatabaseHas('meter_readings', ['id' => $reading->getKey()]);
});

it('does not fail the action when the broadcast throws after commit', function (): void {
    Event::listen(MeterReadingsChanged::class, fn () => throw new RuntimeException('broadcaster down'));
    $user = User::factory()->create();
    $address = Address::factory()->create();
    $user->addresses()->attach($address->getKey(), ['is_primary' => true]);
    $meter = Meter::factory()->create(['address_id' => $address->getKey()]);
    $reading = MeterReading::factory()->create(['meter_id' => $meter->getKey()]);

    app(DeleteMeterReading::class)->handle((int) $user->getKey(), (int) $reading->getKey());

    $this->assertDatabaseMissing('meter_readings', ['id' => $reading->getKey()]);
});
