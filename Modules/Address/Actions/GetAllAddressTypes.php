<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Illuminate\Database\Eloquent\Collection;
use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\AddressType;
use Modules\Address\Repositories\Contracts\AddressTypeRepositoryInterface;

class GetAllAddressTypes
{
    use AsAction;

    public function __construct(private AddressTypeRepositoryInterface $repository) {}

    /** @return Collection<int, AddressType> */
    public function handle(): Collection
    {
        return $this->repository->all();
    }
}
