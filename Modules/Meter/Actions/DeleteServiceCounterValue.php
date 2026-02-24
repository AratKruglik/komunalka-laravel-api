<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Shared\Models\ServiceCounterValue;
use Symfony\Component\HttpFoundation\Response;

class DeleteServiceCounterValue
{
    use AsAction;

    public function __construct(private UserAddressRepositoryInterface $userAddressRepository) {}

    public function handle(int $userId, int $id): void
    {
        $value = ServiceCounterValue::query()->with('serviceCounter')->find($id);

        abort_if($value === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $value->serviceCounter->address_id), Response::HTTP_NOT_FOUND);

        $value->delete();
    }
}
