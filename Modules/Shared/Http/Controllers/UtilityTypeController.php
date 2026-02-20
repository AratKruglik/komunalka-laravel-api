<?php

declare(strict_types=1);

namespace Modules\Shared\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\Shared\Actions\GetActiveUtilityTypes;
use Modules\Shared\Actions\GetUtilityType;
use Modules\Shared\Http\Resources\UtilityTypeResource;

class UtilityTypeController extends Controller
{
    public function index(GetActiveUtilityTypes $action): AnonymousResourceCollection
    {
        return UtilityTypeResource::collection($action->handle());
    }

    public function show(int $utilityType, GetUtilityType $action): UtilityTypeResource
    {
        return new UtilityTypeResource($action->handle($utilityType));
    }
}
