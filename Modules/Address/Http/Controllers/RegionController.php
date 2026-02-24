<?php

declare(strict_types=1);

namespace Modules\Address\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Address\Actions\GetAllRegions;
use Modules\Address\Actions\GetRegion;
use Modules\Address\Http\Resources\RegionResource;

class RegionController extends Controller
{
    public function index(GetAllRegions $action): AnonymousResourceCollection
    {
        return RegionResource::collection($action->handle());
    }

    public function show(int $id, GetRegion $action): RegionResource
    {
        return new RegionResource($action->handle($id));
    }
}
