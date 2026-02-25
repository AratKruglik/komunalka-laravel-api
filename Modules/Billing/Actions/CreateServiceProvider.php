<?php

declare(strict_types=1);

namespace Modules\Billing\Actions;

use Illuminate\Support\Facades\DB;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Billing\DTO\CreateServiceProviderData;
use Modules\Billing\Models\ServiceProvider;
use Modules\Billing\Repositories\Contracts\ServiceProviderRepositoryInterface;
use Modules\Billing\Repositories\Contracts\TariffRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class CreateServiceProvider
{
    use AsAction;

    public function __construct(
        private ServiceProviderRepositoryInterface $serviceProviderRepository,
        private TariffRepositoryInterface $tariffRepository,
        private UserAddressRepositoryInterface $userAddressRepository,
    ) {}

    public function handle(int $userId, CreateServiceProviderData $data): ServiceProvider
    {
        abort_if(! $this->userAddressRepository->userOwnsAddress($userId, $data->addressId), Response::HTTP_NOT_FOUND);

        /** @var ServiceProvider */
        return DB::transaction(function () use ($data) {
            /** @var ServiceProvider $provider */
            $provider = $this->serviceProviderRepository->create([
                'name' => $data->name,
                'description' => $data->description,
                'phone' => $data->phone,
                'email' => $data->email,
                'website' => $data->website,
                'address_id' => $data->addressId,
                'utility_type_id' => $data->utilityTypeId,
                'is_active' => $data->isActive,
            ]);

            foreach ($data->tariffs as $tariffData) {
                $this->tariffRepository->create([
                    'service_provider_id' => $provider->id,
                    'utility_type_id' => $tariffData->utilityTypeId,
                    'currency_id' => $tariffData->currencyId,
                    'name' => $tariffData->name,
                    'base_rate' => $tariffData->baseRate,
                    'service_fee' => $tariffData->serviceFee,
                    'effective_from' => $tariffData->effectiveFrom,
                    'effective_to' => $tariffData->effectiveTo,
                    'notes' => $tariffData->notes,
                ]);
            }

            return $provider->load(['utilityType', 'tariffs.currency', 'tariffs.utilityType']);
        });
    }
}
