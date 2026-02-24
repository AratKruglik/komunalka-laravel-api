<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\AddressType;
use Modules\Address\Repositories\Contracts\AddressTypeRepositoryInterface;

class GetAddressType
{
    use AsAction;

    public function __construct(private AddressTypeRepositoryInterface $repository) {}

    public function handle(int $id): AddressType
    {
        /** @var AddressType */
        return $this->repository->findOrFail($id);
    }
}
