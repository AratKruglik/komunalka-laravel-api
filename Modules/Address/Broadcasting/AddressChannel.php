<?php

declare(strict_types=1);

namespace Modules\Address\Broadcasting;

use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;
use Modules\Auth\Models\User;

class AddressChannel
{
    public function __construct(private UserAddressRepositoryInterface $userAddressRepository) {}

    public function join(User $user, string $addressId): bool
    {
        return $this->userAddressRepository->userOwnsAddress((int) $user->getKey(), (int) $addressId);
    }
}
