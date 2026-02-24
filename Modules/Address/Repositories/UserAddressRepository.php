<?php

declare(strict_types=1);

namespace Modules\Address\Repositories;

use Modules\Address\Models\UserAddress;
use Modules\Address\Repositories\Contracts\UserAddressRepositoryInterface;

class UserAddressRepository implements UserAddressRepositoryInterface
{
    public function attach(int $userId, int $addressId, bool $isPrimary = false): void
    {
        UserAddress::query()->create([
            'user_id' => $userId,
            'address_id' => $addressId,
            'is_primary' => $isPrimary,
        ]);
    }

    public function detach(int $userId, int $addressId): void
    {
        UserAddress::query()
            ->where('user_id', $userId)
            ->where('address_id', $addressId)
            ->delete();
    }

    public function setPrimary(int $userId, int $addressId): void
    {
        $this->clearPrimary($userId);

        UserAddress::query()
            ->where('user_id', $userId)
            ->where('address_id', $addressId)
            ->update(['is_primary' => true]);
    }

    public function clearPrimary(int $userId): void
    {
        UserAddress::query()
            ->where('user_id', $userId)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }

    public function userOwnsAddress(int $userId, int $addressId): bool
    {
        return UserAddress::query()
            ->where('user_id', $userId)
            ->where('address_id', $addressId)
            ->exists();
    }
}
