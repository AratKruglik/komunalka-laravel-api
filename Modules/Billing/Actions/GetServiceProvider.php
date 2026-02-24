<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetServiceProvider
{
    use AsAction;

    public function __construct(
        private ServiceProviderRepositoryInterface $repository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, int $serviceProviderId): ServiceProvider
    {
        $provider = $this->repository->findWithTariffs($serviceProviderId);

        abort_if($provider === null, Response::HTTP_NOT_FOUND);
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $provider->address_id), Response::HTTP_NOT_FOUND);

        return $provider;
    }
}
