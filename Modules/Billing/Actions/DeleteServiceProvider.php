<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

class DeleteServiceProvider
{
    use AsAction;

    public function __construct(private ServiceProviderRepositoryInterface $repository) {}

    public function handle(ServiceProvider $provider): void
    {
        $this->repository->delete($provider);
    }

    public function asController(Request $request, string $provider): RedirectResponse
    {
        $providerModel = GetServiceProvider::run((int) $request->user()->getKey(), (int) $provider);
        $this->handle($providerModel);

        return redirect()->route('providers.index')->with('success', 'Провайдера видалено');
    }
}
