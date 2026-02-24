<?php

declare(strict_types=1);

namespace Modules\Address\Actions;

use Lorisleiva\Actions\Concerns\AsAction;
use Modules\Address\Models\Address;
use Modules\Address\Repositories\Contracts\AddressRepositoryInterface;
use Symfony\Component\HttpFoundation\Response;

class GetUserAddress
{
    use AsAction;

    public function __construct(private AddressRepositoryInterface $repository) {}

    public function handle(int $userId, int $addressId): Address
    {
        $address = $this->repository->findForUser($userId, $addressId);

        abort_if($address === null, Response::HTTP_NOT_FOUND);

        return $address;
    }
}
