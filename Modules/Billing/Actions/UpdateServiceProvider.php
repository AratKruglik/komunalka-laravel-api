<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Illuminate\Http\RedirectResponse;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Billing\DTO\UpdateServiceProviderData;
use Modules\Billing\Http\Requests\UpdateServiceProviderRequest;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;

class UpdateServiceProvider
{
    use AsAction;

    public function __construct(private ServiceProviderRepositoryInterface $repository) {}

    public function handle(ServiceProvider $provider, UpdateServiceProviderData $data): ServiceProvider
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

    public function asController(UpdateServiceProviderRequest $request, string $provider): RedirectResponse
    {
        $providerModel = GetServiceProvider::run((int) $request->user()->getKey(), (int) $provider);
        $this->handle($providerModel, UpdateServiceProviderData::fromRequest($request));

        return redirect()->route('providers.index')->with('success', 'Провайдера оновлено');
    }
}
