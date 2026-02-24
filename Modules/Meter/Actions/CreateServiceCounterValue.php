<?php

declare(strict_types=1);

namespace Modules\Meter\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Shared\Models\ServiceCounter;
use Modules\Shared\Models\ServiceCounterValue;
use Symfony\Component\HttpFoundation\Response;

class CreateServiceCounterValue
{
    use AsAction;

    public function __construct(private UserAddressRepositoryInterface $userAddressRepository) {}

    public function handle(int $userId, int $serviceCounterId, float $value): ServiceCounterValue
    {
        $counter = ServiceCounter::query()->find($serviceCounterId);

        abort_if($counter === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $counter->address_id), Response::HTTP_NOT_FOUND);

        /** @var ServiceCounterValue $counterValue */
        $counterValue = ServiceCounterValue::query()->create([
            'service_counter_id' => $serviceCounterId,
            'value' => $value,
        ]);

        return $counterValue->load('serviceCounter');
    }
}
