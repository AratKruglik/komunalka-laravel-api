<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\JsonApi\JsonApiResource;

class InertiaJsonApiResource extends JsonApiResource
{
    public function __construct(mixed $resource)
    {
        parent::__construct($resource);

        $this->includePreviouslyLoadedRelationships();
    }
}
