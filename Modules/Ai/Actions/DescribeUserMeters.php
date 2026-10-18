<?php

declare(strict_types=1);

namespace Modules\Ai\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Address;
use Modules\Auth\Models\User;
use Modules\Billing\Models\Tariff;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;

class DescribeUserMeters
{
    use AsAction;

    public const array SUPPORTED_UTILITY_SLUGS = ['cold-water', 'hot-water', 'electricity', 'gas'];

    public function __construct(private readonly MeterReadingRepositoryInterface $readings) {}

    /** @return list<array<string, mixed>> */
    public function handle(User $user): array
    {
        $today = CarbonImmutable::now((string) config('app.timezone'))->startOfDay();

        return $this->meters($user)
            ->map(fn (Meter $meter): array => $this->describe($meter, $today))
            ->values()
            ->all();
    }

    public function addressLabel(Address $address): string
    {
        $building = trim($address->street.' '.$address->building_number);
        $label = trim($address->city.', '.$building, ', ');

        return $address->apartment_number !== null && $address->apartment_number !== ''
            ? $label.', кв. '.$address->apartment_number
            : $label;
    }

    /** @return Collection<int, Meter> */
    private function meters(User $user): Collection
    {
        return Meter::query()
            ->active()
            ->whereIn('address_id', $user->addresses()->select('addresses.id'))
            ->whereHas('utilityType', fn ($query) => $query->whereIn('slug', self::SUPPORTED_UTILITY_SLUGS))
            ->with(['address', 'utilityType'])
            ->orderBy('address_id')
            ->orderBy('name')
            ->get();
    }

    /** @return array<string, mixed> */
    private function describe(Meter $meter, CarbonImmutable $today): array
    {
        $zones = $this->zones($meter, $today);

        return [
            'meter_id' => $meter->getKey(),
            'name' => $meter->name,
            'location' => $meter->location,
            'serial_number' => $meter->serial_number,
            'utility_type' => $meter->utilityType->slug,
            'unit' => $meter->utilityType->unit,
            'address' => $this->addressLabel($meter->address),
            'initial_reading' => $meter->initial_reading,
            'tariff_zones' => $zones->map(fn (Tariff $tariff): array => [
                'tariff_id' => $tariff->getKey(),
                'name' => $tariff->name,
                'last_reading' => $this->lastReading($meter, $tariff->getKey()),
            ])->values()->all(),
            'last_reading' => $zones->isEmpty() ? $this->lastReading($meter, null) : null,
        ];
    }

    /** @return Collection<int, Tariff> */
    private function zones(Meter $meter, CarbonImmutable $date): Collection
    {
        if ($meter->service_provider_id === null) {
            return new Collection;
        }

        return Tariff::query()
            ->where('service_provider_id', $meter->service_provider_id)
            ->where('utility_type_id', $meter->utility_type_id)
            ->effectiveAt($date)
            ->orderBy('name')
            ->get();
    }

    /** @return array{value: float, date: string}|null */
    private function lastReading(Meter $meter, ?int $tariffId): ?array
    {
        /** @var MeterReading|null $reading */
        $reading = $this->readings->getLatestForMeterAndTariff($meter->getKey(), $tariffId);

        return $reading === null ? null : [
            'value' => $reading->reading_value,
            'date' => $reading->reading_date->toDateString(),
        ];
    }
}
