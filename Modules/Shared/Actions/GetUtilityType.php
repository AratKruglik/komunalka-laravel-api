<?php

declare(strict_types=1);

namespace Modules\Shared\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Shared\Models\UtilityType;
use Modules\Shared\Repositories\Contracts\UtilityTypeRepositoryInterface;

class GetUtilityType
{
    use AsAction;

    public function __construct(private UtilityTypeRepositoryInterface $repository) {}

    public function handle(int $id): UtilityType
    {
        /** @var UtilityType */
        return $this->repository->findOrFail($id);
    }
}
