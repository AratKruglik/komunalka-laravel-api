<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Events\MeterReadingsChanged;
use Modules\Meter\Repositories\Contracts\MeterReadingRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class DeleteMeterReading
{
    use AsAction;

    public function __construct(
        private MeterReadingRepositoryInterface $meterReadingRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $readingId): void
    {
        $reading = $this->meterReadingRepository->findWithRelations($readingId);

        abort_if($reading === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $reading->meter->address_id), Response::HTTP_NOT_FOUND);

        $addressId = (int) $reading->meter->address_id;
        $readingId = (int) $reading->getKey();
        $meterId = (int) $reading->meter_id;

        $this->meterReadingRepository->delete($reading);

        MeterReadingsChanged::dispatchSafely($addressId, 'deleted', [$readingId], [$meterId]);
    }

    public function asController(Request $request, string $reading): RedirectResponse
    {
        $this->handle(
            (int) $request->user()->getKey(),
            (int) $reading,
        );

        return redirect()
            ->back()
            ->with('success', 'Показання успішно видалено.');
    }
}
