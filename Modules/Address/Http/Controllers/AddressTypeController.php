<?php

declare(strict_types=1);

namespace Modules\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Address\Actions\GetAddressType;
use Modules\Address\Actions\GetAllAddressTypes;
use Modules\Address\Http\Resources\AddressTypeResource;

class AddressTypeController extends Controller
{
    public function index(GetAllAddressTypes $action): AnonymousResourceCollection
    {
        return AddressTypeResource::collection($action->handle());
    }

    public function show(int $id, GetAddressType $action): AddressTypeResource
    {
        return new AddressTypeResource($action->handle($id));
    }
}
