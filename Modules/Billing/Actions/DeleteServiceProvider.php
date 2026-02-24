<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

class DeleteServiceProvider
{
    use AsAction;

    public function __construct(private ServiceProviderRepositoryInterface $repository) {}

    public function handle(int $userId, ServiceProvider $provider): void
    {
        $this->repository->delete($provider);
    }
}
