<?php

declare(strict_types=1);

namespace Modules\Export\Actions;

use Illuminate\Database\Eloquent\Collection;
use League\Csv\Writer;
use Modules\Meter\Models\MeterReading;
use SplTempFileObject;

class ExportToCsv
{
    /** @param Collection<int, MeterReading> $readings */
    public function handle(Collection $readings): string
    {
        $writer = Writer::createFromFileObject(new SplTempFileObject);
        $writer->setOutputBOM(Writer::BOM_UTF8);

        $writer->insertOne([
            'Дата',
            'Адреса',
            'Лічильник',
            'Тип послуги',
            'Попереднє значення',
            'Поточне значення',
            'Споживання',
            'Примітки',
        ]);

        foreach ($readings as $reading) {
            $writer->insertOne([
                $reading->reading_date?->format('d.m.Y'),
                $this->formatAddress($reading),
                $reading->meter?->name ?? $reading->meter?->serial_number,
                $reading->meter?->utilityType?->display_name,
                number_format((float) $reading->previous_reading_value, 2, '.', ''),
                number_format((float) $reading->reading_value, 2, '.', ''),
                number_format((float) $reading->consumption, 2, '.', ''),
                $reading->notes,
            ]);
        }

        return $writer->toString();
    }

    private function formatAddress(MeterReading $reading): string
    {
        $address = $reading->meter?->address;

        if (! $address) {
            return '';
        }

        $parts = [$address->city, $address->street, $address->building_number];

        if ($address->apartment_number) {
            $parts[] = 'кв. '.$address->apartment_number;
        }

        return implode(', ', array_filter($parts));
    }
}
