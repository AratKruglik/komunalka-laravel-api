<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Modules\Meter\Events\MeterReadingsChanged;

mutates(MeterReadingsChanged::class);

it('broadcasts an identifiers-only payload on the address private channel', function (): void {
    CarbonImmutable::setTestNow('2026-06-15 12:00:00');

    $event = new MeterReadingsChanged(7, 'created', [1, 2], [3]);

    expect($event->broadcastOn())->toBeInstanceOf(PrivateChannel::class)
        ->and($event->broadcastOn()->name)->toBe('private-address.7')
        ->and($event->broadcastAs())->toBe('meter-readings.changed')
        ->and($event->broadcastWith())->toBe([
            'addressId' => 7,
            'action' => 'created',
            'readingIds' => [1, 2],
            'meterIds' => [3],
            'occurredAt' => '2026-06-15T12:00:00+00:00',
        ]);

    CarbonImmutable::setTestNow();
});

it('defaults ids to empty lists', function (): void {
    $event = new MeterReadingsChanged(1, 'deleted');

    expect($event->readingIds)->toBe([])->and($event->meterIds)->toBe([]);
});
