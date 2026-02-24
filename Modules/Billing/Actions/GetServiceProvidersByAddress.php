<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetServiceProvidersByAddress
{
    use AsAction;

    public function __construct(
        private ServiceProviderRepositoryInterface $repository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    /** @return Collection<int, ServiceProvider> */
    public function handle(int $userId, int $addressId): Collection
    {
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $addressId), Response::HTTP_NOT_FOUND);

        return $this->repository->getByAddressId($addressId);
    }
}
