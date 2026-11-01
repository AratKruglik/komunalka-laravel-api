<?php

declare(strict_types=1);

namespace Modules\Meter\Events;

use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Facades\DB;

final class MeterReadingsChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use InteractsWithSockets;

    public readonly string $occurredAt;

    /**
     * @param  'created'|'deleted'  $action
     * @param  list<int>  $readingIds
     * @param  list<int>  $meterIds
     */
    public function __construct(
        public readonly int $addressId,
        public readonly string $action,
        public readonly array $readingIds = [],
        public readonly array $meterIds = [],
    ) {
        $this->occurredAt = CarbonImmutable::now()->toIso8601String();
    }

    /**
     * @param  'created'|'deleted'  $action
     * @param  list<int>  $readingIds
     * @param  list<int>  $meterIds
     */
    public static function dispatchSafely(int $addressId, string $action, array $readingIds = [], array $meterIds = []): void
    {
        DB::afterCommit(fn () => rescue(fn () => event(new self($addressId, $action, $readingIds, $meterIds))));
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('address.'.$this->addressId);
    }

    public function broadcastAs(): string
    {
        return 'meter-readings.changed';
    }

    /**
     * @return array{addressId: int, action: string, readingIds: list<int>, meterIds: list<int>, occurredAt: string}
     */
    public function broadcastWith(): array
    {
        return [
            'addressId' => $this->addressId,
            'action' => $this->action,
            'readingIds' => $this->readingIds,
            'meterIds' => $this->meterIds,
            'occurredAt' => $this->occurredAt,
        ];
    }
}
