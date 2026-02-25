<?php

declare(strict_types=1);

namespace Modules\Export\Actions;

use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Export\DTO\ExportRequestData;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class ExportMeterReadings
{
    public function __construct(
        private UserAddressRepositoryInterface $userAddressRepository,
        private MeterRepositoryInterface $meterRepository,
        private MeterReadingRepositoryInterface $meterReadingRepository,
        private ExportToCsv $exportToCsv,
        private ExportToPdf $exportToPdf,
    ) {}

    public function handle(int $userId, ExportRequestData $data): Response
    {
        foreach ($data->addressIds as $addressId) {
            abort_if(
                ! $this->userAddressRepository->userOwnsAddress($userId, $addressId),
                Response::HTTP_FORBIDDEN,
                'Доступ до однієї або кількох вказаних адрес заборонено.',
            );
        }

        $meterIds = $this->meterRepository
            ->getByAddressIds($data->addressIds)
            ->pluck('id')
            ->toArray();

        $readings = $this->meterReadingRepository->getByMeterIds($meterIds, $data->fromDate, $data->toDate);

        if ($data->format === 'csv') {
            $content = $this->exportToCsv->handle($readings);

            return response($content, Response::HTTP_OK, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="meter-readings.csv"',
            ]);
        }

        $content = $this->exportToPdf->handle($readings, $data->fromDate, $data->toDate);

        return response($content, Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="meter-readings.pdf"',
        ]);
    }
}
