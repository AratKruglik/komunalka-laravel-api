<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Actions\CalculateTariffCost;
use Modules\Billing\Actions\GetEffectiveTariff;
use Modules\Billing\DTO\TariffCalculationResult;
use Modules\Billing\Models\Tariff;
use Modules\Billing\Repositories\Contracts\TariffRepositoryInterface;
use Modules\Meter\DTO\BatchReadingData;
use Modules\Meter\DTO\BatchReadingResult;
use Modules\Meter\Http\Requests\BatchMeterReadingRequest;
use Modules\Meter\Models\Meter;
use Modules\Meter\Models\MeterReading;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class CreateBatchReadings
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private MeterReadingRepositoryInterface $meterReadingRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
        private TariffRepositoryInterface $tariffRepository,
        private GetEffectiveTariff $getEffectiveTariff,
        private CalculateTariffCost $calculateTariffCost,
    ) {}

    public function handle(int $userId, BatchReadingData $data): BatchReadingResult
    {
        return DB::transaction(function () use ($userId, $data): BatchReadingResult {
            $meterIds = array_map(fn ($r) => $r->meterId, $data->readings);

            $meters = $this->meterRepository->findManyWithRelations($meterIds)->keyBy('id');

            $addressIds = $meters->pluck('address_id')->unique()->values()->all();
            abort_if(
                ! $this->userAddressRepository->userOwnsAddresses($userId, $addressIds),
                Response::HTTP_NOT_FOUND,
            );

            /** @var array<int, MeterReading> $readings */
            $readings = [];

            /** @var array<int, TariffCalculationResult> $tariffCalculations */
            $tariffCalculations = [];

            /** @var array<int, true> $attachedPhotoMeterIds */
            $attachedPhotoMeterIds = [];

            foreach ($data->readings as $index => $reading) {
                /** @var Meter|null $meter */
                $meter = $meters->get($reading->meterId);

                abort_if($meter === null, Response::HTTP_NOT_FOUND);

                $previousReading = $this->meterReadingRepository->getLatestForMeterAndTariff(
                    $reading->meterId,
                    $reading->tariffId,
                );
                $previousValue = $previousReading ? $previousReading->reading_value : $meter->initial_reading;

                if ($reading->readingValue < $previousValue) {
                    throw ValidationException::withMessages([
                        "readings.{$index}.reading_value" => ['Значення показника не може бути менше попереднього.'],
                    ]);
                }

                $consumption = $reading->readingValue - $previousValue;

                $tariff = $this->resolveTariff($reading->tariffId, $meter, $reading->readingDate, $index);

                /** @var MeterReading $meterReading */
                $meterReading = $this->meterReadingRepository->create([
                    'meter_id' => $reading->meterId,
                    'reading_value' => $reading->readingValue,
                    'reading_date' => $reading->readingDate,
                    'previous_reading_value' => $previousValue,
                    'consumption' => $consumption,
                    'notes' => $reading->notes,
                    'is_estimated' => $reading->isEstimated,
                    'tariff_id' => $tariff?->getKey(),
                ]);

                if (isset($data->photos[$reading->meterId]) && ! isset($attachedPhotoMeterIds[$reading->meterId])) {
                    foreach ($data->photos[$reading->meterId] as $photo) {
                        $meterReading->addMedia($photo)->toMediaCollection('photos');
                    }
                    $attachedPhotoMeterIds[$reading->meterId] = true;
                }

                if ($tariff !== null && $consumption > 0) {
                    $tariffCalculations[] = $this->calculateTariffCost->handle(
                        $tariff,
                        (string) $consumption,
                        $meter->getKey(),
                        $meter->name,
                        $meter->utilityType->unit,
                    );
                }

                $readings[] = $meterReading;
            }

            return new BatchReadingResult($readings, $tariffCalculations);
        });
    }

    private function resolveTariff(?int $tariffId, Meter $meter, string $readingDate, int $index): ?Tariff
    {
        if ($tariffId === null) {
            if ($meter->service_provider_id === null) {
                return null;
            }

            return $this->getEffectiveTariff->handle(
                $meter->service_provider_id,
                $meter->utility_type_id,
                CarbonImmutable::parse($readingDate),
            );
        }

        /** @var Tariff|null $tariff */
        $tariff = $this->tariffRepository->find($tariffId);

        if ($tariff === null) {
            throw ValidationException::withMessages([
                "readings.{$index}.tariff_id" => ['Обраний тариф не існує.'],
            ]);
        }

        if (
            $tariff->service_provider_id !== $meter->service_provider_id
            || $tariff->utility_type_id !== $meter->utility_type_id
        ) {
            throw ValidationException::withMessages([
                "readings.{$index}.tariff_id" => ['Обраний тариф не відповідає провайдеру або типу комунальної послуги лічильника.'],
            ]);
        }

        return $tariff;
    }

    public function asController(BatchMeterReadingRequest $request): RedirectResponse
    {
        $this->handle(
            (int) $request->user()->getKey(),
            BatchReadingData::fromRequest($request),
        );

        return redirect()
            ->route('readings.index')
            ->with('success', 'Показання успішно збережено!');
    }
}
