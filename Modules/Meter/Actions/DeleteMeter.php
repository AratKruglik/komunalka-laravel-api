<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Meter\Repositories\Contracts\MeterRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class DeleteMeter
{
    use AsAction;

    public function __construct(
        private MeterRepositoryInterface $meterRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $meterId): void
    {
        $meter = $this->meterRepository->findWithRelations($meterId);

        abort_if($meter === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $meter->address_id), Response::HTTP_NOT_FOUND);

        $this->meterRepository->delete($meter);
    }

    public function asController(Request $request, string $meter): RedirectResponse
    {
        $this->handle((int) $request->user()->getKey(), (int) $meter);

        return redirect()->route('meters.index')->with('success', 'Лічильник видалено');
    }
}
