<?php

declare(strict_types=1);

namespace Modules\Billing\Policies;

use Modules\Address\Models\UserAddress;
use Modules\Auth\Models\User;
use Modules\Billing\Models\ServiceProvider;

class ServiceProviderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function view(User $user, ServiceProvider $provider): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $provider->address_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, ServiceProvider $provider): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $provider->address_id)
            ->exists();
    }

    public function delete(User $user, ServiceProvider $provider): bool
    {
        return UserAddress::query()
            ->where('user_id', $user->id)
            ->where('address_id', $provider->address_id)
            ->exists();
    }
}
