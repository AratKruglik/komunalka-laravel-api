<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Meter\Http\Requests\UploadMeterPhotoRequest;
use Modules\Meter\Models\Meter;

class UploadMeterPhoto
{
    use AsAction;

    public function handle(Meter $meter, UploadedFile $photo): Meter
    {
        $meter->addMedia($photo)->toMediaCollection('photo');

        return $meter->refresh();
    }

    public function asController(UploadMeterPhotoRequest $request, string $meterId): RedirectResponse
    {
        $meter = GetMeter::run((int) $request->user()->getKey(), (int) $meterId);
        $this->handle($meter, $request->file('photo'));

        return redirect()->back()->with('success', 'Фото завантажено');
    }
}
