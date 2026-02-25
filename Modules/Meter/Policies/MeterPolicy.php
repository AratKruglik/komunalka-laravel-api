<?php

declare(strict_types=1);

namespace Modules\Meter\Policies;

use Modules\Address\Models\UserAddress;
use Modules\Auth\Models\User;
use Modules\Meter\Models\Meter;

class MeterPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, Meter $meter): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $meter->address_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Meter $meter): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $meter->address_id)
            ->exists();
    }

    public function delete(User $user, Meter $meter): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $meter->address_id)
            ->exists();
    }
}
