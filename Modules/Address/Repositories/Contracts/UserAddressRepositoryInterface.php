<?php

declare(strict_types=1);

namespace Modules\Address\Repositories\Contracts;

interface UserAddressRepositoryInterface
{
    public function attach(int $userId, int $addressId, bool $isPrimary = false): void;

    public function detach(int $userId, int $addressId): void;

    public function setPrimary(int $userId, int $addressId): void;

    public function clearPrimary(int $userId): void;

    public function userOwnsAddress(int $userId, int $addressId): bool;

    /** @param array<int> $addressIds */
    public function userOwnsAddresses(int $userId, array $addressIds): bool;
}
