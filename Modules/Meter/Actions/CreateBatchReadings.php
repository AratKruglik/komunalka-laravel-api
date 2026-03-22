<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Actions\CalculateTariffCost;
use Modules\Billing\Actions\GetEffectiveTariff;
use Modules\Billing\DTO\TariffCalculationResult;
use Modules\Meter\DTO\BatchReadingData;
use Modules\Meter\DTO\BatchReadingResult;
use Modules\Meter\Http\Requests\BatchMeterReadingRequest;
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
        private GetEffectiveTariff $getEffectiveTariff,
        private CalculateTariffCost $calculateTariffCost,
    ) {}

    public function handle(int $userId, BatchReadingData $data): BatchReadingResult
    {
        return DB::transaction(function () use ($userId, $data): BatchReadingResult {
            $meterIds = array_map(fn ($r) => $r->meterId, $data->readings);

            $meters = $this->meterRepository->findManyWithRelations($meterIds)->keyBy('id');
            $latestReadings = $this->meterReadingRepository->getLatestForMeters($meterIds);

            $addressIds = $meters->pluck('address_id')->unique()->values()->all();
            abort_if(
                ! $this->userAddressRepository->userOwnsAddresses($userId, $addressIds),
                Response::HTTP_NOT_FOUND,
            );

            /** @var array<int, MeterReading> $readings */
            $readings = [];

            /** @var array<int, TariffCalculationResult> $tariffCalculations */
            $tariffCalculations = [];

            foreach ($data->readings as $reading) {
                $meter = $meters->get($reading->meterId);

                abort_if($meter === null, Response::HTTP_NOT_FOUND);

                $previousReading = $latestReadings->get($reading->meterId);
                $previousValue = $previousReading ? $previousReading->reading_value : $meter->initial_reading;

                abort_if(
                    $reading->readingValue < $previousValue,
                    Response::HTTP_UNPROCESSABLE_ENTITY,
                    'Значення показника не може бути менше попереднього.',
                );

                $consumption = $reading->readingValue - $previousValue;

                /** @var MeterReading $meterReading */
                $meterReading = $this->meterReadingRepository->create([
                    'meter_id' => $reading->meterId,
                    'reading_value' => $reading->readingValue,
                    'reading_date' => $reading->readingDate,
                    'previous_reading_value' => $previousValue,
                    'consumption' => $consumption,
                    'notes' => $reading->notes,
                    'is_estimated' => $reading->isEstimated,
                ]);

                $tariff = null;

                if ($meter->service_provider_id !== null) {
                    $tariff = $this->getEffectiveTariff->handle(
                        $meter->service_provider_id,
                        $meter->utility_type_id,
                        CarbonImmutable::parse($reading->readingDate),
                    );

                    if ($tariff !== null) {
                        $meterReading->tariff_id = $tariff->getKey();
                        $meterReading->save();
                    }
                }

                if (isset($data->photos[$reading->meterId])) {
                    foreach ($data->photos[$reading->meterId] as $photo) {
                        $meterReading->addMedia($photo)->toMediaCollection('photos');
                    }
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
