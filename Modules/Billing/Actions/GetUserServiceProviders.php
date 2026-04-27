<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\UserAddress;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

class GetUserServiceProviders
{
    use AsAction;

    public function __construct(private ServiceProviderRepositoryInterface $repository) {}

    /** @return Collection<int, ServiceProvider> */
    public function handle(int $userId): Collection
    {
        $addressIds = UserAddress::query()
            ->where('user_id', $userId)
            ->pluck('address_id')
            ->all();

        Log::debug('Retrieving providers for user', [
            'user_id' => $userId,
            'address_ids' => $addressIds,
        ]);

        $providers = $this->repository->getByAddressIds($addressIds);

        Log::debug('Providers retrieved', [
            'count' => $providers->count(),
        ]);

        return $providers;
    }
}
