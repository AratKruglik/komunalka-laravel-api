<?php

declare(strict_types=1);

namespace Modules\Address\Policies;

use Modules\Address\Models\Address;
use Modules\Auth\Models\User;

class AddressPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Address $address): bool
    {
        return $user->addresses()->where('addresses.id', $address->id)->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Address $address): bool
    {
        return $user->addresses()->where('addresses.id', $address->id)->exists();
    }

    public function delete(User $user, Address $address): bool
    {
        return $user->addresses()->where('addresses.id', $address->id)->exists();
    }
}
