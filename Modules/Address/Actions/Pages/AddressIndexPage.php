<?php

declare(strict_types=1);

namespace Modules\Address\Actions\Pages;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Actions\GetUserAddresses;
use Modules\Address\DTO\AddressPaginationData;
use Modules\Address\Http\Resources\AddressResource;

class AddressIndexPage
{
    use AsAction;

    public function handle(Request $request): Response
    {
        $pagination = AddressPaginationData::fromRequest($request);
        $addresses = GetUserAddresses::run((int) $request->user()->getKey(), $pagination);

        return Inertia::render('Addresses/Index', [
            'addresses' => AddressResource::collection($addresses),
        ]);
    }
}
