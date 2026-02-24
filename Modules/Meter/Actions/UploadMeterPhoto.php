<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Meter\Models\Meter;

class UploadMeterPhoto
{
    use AsAction;

    public function handle(Meter $meter, UploadedFile $photo): Meter
    {
        $meter->addMedia($photo)->toMediaCollection('photo');

        return $meter->refresh();
    }
}
