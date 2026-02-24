<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\DTOs\UpdateServiceProviderData;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

class UpdateServiceProvider
{
    use AsAction;

    public function __construct(private ServiceProviderRepositoryInterface $repository) {}

    public function handle(int $userId, ServiceProvider $provider, UpdateServiceProviderData $data): ServiceProvider
    {
        /** @var ServiceProvider */
        $updated = $this->repository->update($provider, [
            'name' => $data->name,
            'description' => $data->description,
            'phone' => $data->phone,
            'email' => $data->email,
            'website' => $data->website,
            'is_active' => $data->isActive,
            'utility_type_id' => $data->utilityTypeId,
        ]);

        return $updated->load(['utilityType', 'tariffs.currency']);
    }
}
