<?php

declare(strict_types=1);

namespace Modules\Address\Actions\Pages;

use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Actions\GetAllAddressTypes;
use Modules\Address\Actions\GetAllRegions;
use Modules\Address\Http\Resources\AddressTypeResource;
use Modules\Address\Http\Resources\RegionResource;

class AddressCreatePage
{
    use AsAction;

    public function handle(): Response
    {
        return Inertia::render('Addresses/Create', [
            'regions' => RegionResource::collection(GetAllRegions::run()),
            'addressTypes' => AddressTypeResource::collection(GetAllAddressTypes::run()),
        ]);
    }
}
